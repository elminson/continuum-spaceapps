<script setup lang="ts">
import { locale, number, t } from '@/lib/i18n';
import * as echarts from 'echarts';
import { computed } from 'vue';
import VChart from 'vue-echarts';
import type { Month } from './types';

void echarts;

const props = defineProps<{ months: Month[] }>();

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
            orient: 'horizontal',
            left: 'center',
            bottom: 0,
            inRange: { color: ['#fff7ed', '#fdba74', '#f97316', '#c2410c', '#7c2d12'] },
        },
        series: [{ type: 'heatmap', data, itemStyle: { borderColor: '#fff', borderWidth: 1 } }],
    };
});
</script>

<template>
    <VChart :option="option" autoresize class="w-full" :style="{ height: `${Math.max(300, years.length * 18 + 48)}px` }" />
</template>
