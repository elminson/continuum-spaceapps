<script setup lang="ts">
import { locale, number, t } from '@/lib/i18n';
import { isDark } from '@/lib/theme';
import * as echarts from 'echarts';
import { computed, ref } from 'vue';
import VChart from 'vue-echarts';
import type { Month } from './types';

void echarts;

const props = defineProps<{ months: Month[] }>();
const chart = ref<InstanceType<typeof VChart> | null>(null);

function download() {
    const url = chart.value?.getDataURL({ type: 'png', pixelRatio: 2, backgroundColor: isDark.value ? '#0f1113' : '#ffffff' });
    if (!url) return;
    const a = document.createElement('a');
    a.href = url;
    a.download = `continuum-fire-calendar-${years.value[0]}-${years.value[years.value.length - 1]}.png`;
    a.click();
}

const monthNames = computed(() =>
    Array.from({ length: 12 }, (_, i) =>
        new Intl.DateTimeFormat(locale() === 'es' ? 'es-DO' : 'en-US', { month: 'short' }).format(new Date(2020, i, 1)),
    ),
);
const years = computed(() => [...new Set(props.months.map((m) => m.month.slice(0, 4)))]);

const option = computed(() => {
    const data = props.months
        .filter((m) => m.value !== null)
        .map((m) => [Number(m.month.slice(5, 7)) - 1, years.value.indexOf(m.month.slice(0, 4)), m.value]);
    const max = Math.max(1, ...data.map((d) => d[2] as number));

    return {
        animation: false,
        backgroundColor: 'transparent',
        grid: { left: 48, right: 72, top: 8, bottom: 32 },
        tooltip: {
            formatter: (p: { data: number[] }) =>
                `${monthNames.value[p.data[0]]} ${years.value[p.data[1]]}: <b>${number(p.data[2])}</b> ${t('cell-days')}`,
        },
        xAxis: { type: 'category', data: monthNames.value, splitArea: { show: false } },
        yAxis: { type: 'category', data: years.value, inverse: true },
        visualMap: {
            min: 0,
            max,
            calculable: true,
            orient: 'vertical',
            right: 0,
            top: 'middle',
            itemHeight: 160,
            inRange: {
                color: isDark.value
                    ? ['#1f1a17', '#7c2d12', '#c2410c', '#f97316', '#fdba74']
                    : ['#fff7ed', '#fdba74', '#f97316', '#c2410c', '#7c2d12'],
            },
        },
        series: [{ type: 'heatmap', data, itemStyle: { borderColor: isDark.value ? '#0f1113' : '#fff', borderWidth: 1 } }],
    };
});
</script>

<template>
    <div>
        <VChart
            ref="chart"
            :theme="isDark ? 'dark' : undefined"
            :option="option"
            autoresize
            class="w-full"
            :style="{ height: `${Math.max(300, years.length * 18 + 48)}px` }"
        />
        <div class="mt-1 text-right">
            <button class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline" @click="download">
                {{ t('Download image') }}
            </button>
        </div>
    </div>
</template>
