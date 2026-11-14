<script setup lang="ts">
import { COLORS, number, percent, r, t } from '@/lib/i18n';
import * as echarts from 'echarts';
import { computed, onMounted, ref } from 'vue';
import VChart from 'vue-echarts';
import Kpi from './Kpi.vue';

void echarts;

type Result = {
    available: boolean;
    years: number[];
    year?: number;
    train_days?: number;
    test_days?: number;
    total_error?: number | null;
    improvement?: number;
    monthly_r?: number;
    actual_total?: number;
    estimated_total?: number;
    months?: { month: string; modis: number; estimate: number | null; low: number | null; high: number | null }[];
};

const props = defineProps<{ slug: string }>();
const result = ref<Result | null>(null);
const loading = ref(false);

async function load(year?: number) {
    loading.value = true;
    const response = await fetch(`/api/areas/${props.slug}/retirement${year ? `?year=${year}` : ''}`);
    result.value = (await response.json()).data;
    loading.value = false;
}

onMounted(() => load());

const option = computed(() => {
    const months = result.value?.months ?? [];
    const first = months.findIndex((m) => m.estimate !== null);

    return {
        animation: false,
        grid: { left: 56, right: 16, top: 40, bottom: 32 },
        legend: { top: 0, data: [t('MODIS (real)'), t('VIIRS only → MODIS scale')] },
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: months.map((m) => m.month) },
        yAxis: { type: 'value', name: t('cell-days / month') },
        series: [
            {
                name: 'low',
                type: 'line',
                stack: 'b',
                data: months.map((m) => m.low),
                lineStyle: { opacity: 0 },
                symbol: 'none',
                silent: true,
                tooltip: { show: false },
            },
            {
                name: 'band',
                type: 'line',
                stack: 'b',
                data: months.map((m) => (m.low === null || m.high === null ? null : m.high - m.low)),
                lineStyle: { opacity: 0 },
                areaStyle: { color: COLORS.harmonized, opacity: 0.2 },
                symbol: 'none',
                silent: true,
                tooltip: { show: false },
            },
            {
                name: t('MODIS (real)'),
                type: 'line',
                data: months.map((m) => m.modis),
                color: COLORS.modis,
                symbol: 'none',
                lineStyle: { width: 2 },
                markLine:
                    first >= 0
                        ? {
                              symbol: 'none',
                              silent: true,
                              label: { formatter: t('MODIS off (simulated)') },
                              lineStyle: { type: 'dashed', color: COLORS.muted },
                              data: [{ xAxis: months[first].month }],
                          }
                        : undefined,
            },
            {
                name: t('VIIRS only → MODIS scale'),
                type: 'line',
                data: months.map((m) => m.estimate),
                color: COLORS.harmonized,
                symbol: 'none',
                lineStyle: { width: 2.5 },
            },
        ],
    };
});

const total = computed(() => {
    const e = result.value?.total_error;
    return e === null || e === undefined ? '—' : `${e >= 0 ? '+' : '−'}${percent(Math.abs(e), 1)}`;
});
</script>

<template>
    <div v-if="!result" class="h-72 animate-pulse rounded-lg bg-muted" />
    <p v-else-if="!result.available" class="text-sm text-muted-foreground">{{ t('Not enough overlap yet to simulate the end of MODIS here.') }}</p>
    <div v-else class="flex flex-col gap-4" :class="{ 'opacity-60': loading }">
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-muted-foreground">{{ t('MODIS stops on 1 January') }}</span>
            <button
                v-for="year in result.years"
                :key="year"
                class="rounded-md border px-2 py-1 tabular-nums"
                :class="year === result.year ? 'bg-foreground text-background' : 'hover:bg-muted'"
                @click="load(year)"
            >
                {{ year }}
            </button>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <Kpi
                :label="t('Total after the cutoff')"
                :value="total"
                :hint="t(':estimated estimated vs :actual real', { estimated: number(result.estimated_total), actual: number(result.actual_total) })"
            />
            <Kpi
                :label="t('Daily error vs naive switch')"
                :value="`−${percent(result.improvement)}`"
                :hint="t(':test unseen days, fitted on :train earlier days', { test: number(result.test_days), train: number(result.train_days) })"
            />
            <Kpi :label="t('Monthly correlation')" :value="r(result.monthly_r)" :hint="t('VIIRS-only estimate vs real MODIS')" />
        </div>
        <VChart :option="option" autoresize class="h-72 w-full" />
    </div>
</template>
