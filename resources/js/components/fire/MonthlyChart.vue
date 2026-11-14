<script setup lang="ts">
import { COLORS, number, t } from '@/lib/i18n';
import { isDark } from '@/lib/theme';
import * as echarts from 'echarts';
import { computed } from 'vue';
import VChart from 'vue-echarts';
import type { Month } from './types';

void echarts;

const props = defineProps<{ months: Month[]; scale: string }>();

const option = computed(() => ({
    animation: false,
    backgroundColor: 'transparent',
    grid: { left: 56, right: 16, top: 40, bottom: 64 },
    legend: { top: 0, data: [t('MODIS (1 km)'), t('VIIRS (375 m)'), t('Harmonized (:scale scale)', { scale: props.scale.toUpperCase() })] },
    tooltip: {
        trigger: 'axis',
        formatter: (p: { dataIndex: number }[]) => {
            const m = props.months[p[0].dataIndex];
            return [
                `<b>${m.month}</b>`,
                `${t('Harmonized')}: <b>${number(m.value)}</b>${m.estimated ? ` <i>(${t('estimated')}, ${number(m.low)}–${number(m.high)})</i>` : ''}`,
                `MODIS: ${number(m.modis)}`,
                `VIIRS: ${number(m.viirs)}`,
            ].join('<br>');
        },
    },
    xAxis: { type: 'category', data: props.months.map((m) => m.month) },
    yAxis: { type: 'value', name: t('cell-days / month') },
    dataZoom: [{ type: 'inside' }, { type: 'slider', height: 18, bottom: 12 }],
    series: [
        {
            name: 'low',
            type: 'line',
            stack: 'band',
            data: props.months.map((m) => (m.estimated ? m.low : null)),
            lineStyle: { opacity: 0 },
            symbol: 'none',
            silent: true,
        },
        {
            name: 'band',
            type: 'line',
            stack: 'band',
            data: props.months.map((m) => (m.estimated && m.low !== null && m.high !== null ? m.high - m.low : null)),
            lineStyle: { opacity: 0 },
            areaStyle: { color: COLORS.harmonized, opacity: 0.2 },
            symbol: 'none',
            silent: true,
        },
        {
            name: t('MODIS (1 km)'),
            type: 'line',
            data: props.months.map((m) => m.modis),
            color: COLORS.modis,
            symbol: 'none',
            lineStyle: { width: 1, type: 'dashed' },
        },
        {
            name: t('VIIRS (375 m)'),
            type: 'line',
            data: props.months.map((m) => m.viirs),
            color: COLORS.viirs,
            symbol: 'none',
            lineStyle: { width: 1, type: 'dashed' },
        },
        {
            name: t('Harmonized (:scale scale)', { scale: props.scale.toUpperCase() }),
            type: 'line',
            data: props.months.map((m) => m.value),
            color: COLORS.harmonized,
            symbol: 'none',
            lineStyle: { width: 2.5 },
        },
    ],
}));
</script>

<template>
    <VChart :theme="isDark ? 'dark' : undefined" :option="option" autoresize class="h-80 w-full" />
</template>
