<script setup lang="ts">
import { COLORS, dateRange, number, t } from '@/lib/i18n';
import * as echarts from 'echarts';
import { computed, ref } from 'vue';
import VChart from 'vue-echarts';
import Loading from './Loading.vue';
import TimesNormal from './TimesNormal.vue';

void echarts;

type Result = {
    from: string;
    to: string;
    days: number;
    value: number | null;
    typical: number;
    times: number | null;
    rank: number | null;
    of: number;
    years: { year: number; from: string; value: number }[];
};

const props = defineProps<{ slug: string }>();
const today = new Date().toISOString().slice(0, 10);
const monthAgo = new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10);
const from = ref(monthAgo);
const to = ref(today);
const result = ref<Result | null>(null);
const error = ref<string | null>(null);
const loading = ref(false);

async function compare() {
    loading.value = true;
    error.value = null;
    const response = await fetch(`/api/areas/${props.slug}/compare?from=${from.value}&to=${to.value}`, { headers: { Accept: 'application/json' } });
    const body = await response.json();
    loading.value = false;

    if (!response.ok) {
        error.value = body.message ? t(body.message) : t('Check the dates.');
        return;
    }

    result.value = body.data;
}

compare();

const option = computed(() => {
    const years = result.value?.years ?? [];

    return {
        animation: false,
        grid: { left: 48, right: 8, top: 16, bottom: 28 },
        tooltip: {
            trigger: 'axis',
            formatter: (p: { dataIndex: number }[]) =>
                `${years[p[0].dataIndex].year}: <b>${number(years[p[0].dataIndex].value)}</b> ${t('cell-days')}`,
        },
        xAxis: { type: 'category', data: years.map((y) => y.year), axisLabel: { interval: 'auto' } },
        yAxis: { type: 'value' },
        series: [
            {
                type: 'bar',
                data: years.map((y) => ({ value: y.value, itemStyle: { color: y.from === result.value?.from ? COLORS.viirs : '#cbd5e1' } })),
                barMaxWidth: 18,
            },
            {
                type: 'line',
                data: years.map(() => result.value?.typical ?? null),
                symbol: 'none',
                lineStyle: { type: 'solid', width: 1, color: '#64748b' },
                tooltip: { show: false },
            },
        ],
    };
});
</script>

<template>
    <div>
        <form class="flex flex-wrap items-end gap-2 text-sm" @submit.prevent="compare">
            <label class="flex flex-col gap-1">
                <span class="text-muted-foreground">{{ t('From') }}</span>
                <input v-model="from" type="date" min="2000-11-01" :max="today" class="rounded-md border bg-background px-2 py-1.5" />
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-muted-foreground">{{ t('To') }}</span>
                <input v-model="to" type="date" :min="from" :max="today" class="rounded-md border bg-background px-2 py-1.5" />
            </label>
            <button class="rounded-md bg-foreground px-3 py-1.5 text-background">{{ t('Compare') }}</button>
            <Loading v-if="loading" />
            <span v-if="error" class="text-red-600">{{ error }}</span>
        </form>

        <div v-if="result" class="mt-4" :class="{ 'opacity-60': loading }">
            <p v-if="result.value !== null" class="text-sm">
                <span class="text-lg font-semibold tabular-nums">{{ number(result.value) }}</span>
                {{
                    t('cell-days from :range, against a typical :typical for the same dates.', {
                        range: dateRange(result.from, result.to),
                        typical: number(result.typical),
                    })
                }}
                <TimesNormal class="ml-1" :times="result.times" />
                <span class="text-muted-foreground"> · {{ t('rank :rank of :of years', { rank: result.rank ?? '—', of: result.of }) }}</span>
            </p>
            <p v-else class="text-sm text-muted-foreground">{{ t('Some of these days have no data yet; showing the same dates in other years.') }}</p>
            <VChart :option="option" autoresize class="mt-2 h-56 w-full" />
            <p class="text-xs text-muted-foreground">
                {{ t('Each bar is the same dates in one year, on the MODIS scale. The line is the median.') }}
            </p>
        </div>
    </div>
</template>
