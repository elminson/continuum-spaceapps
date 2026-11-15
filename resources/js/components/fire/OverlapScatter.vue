<script setup lang="ts">
import { COLORS, number, t } from '@/lib/i18n';
import { isDark } from '@/lib/theme';
import * as echarts from 'echarts';
import { computed } from 'vue';
import VChart from 'vue-echarts';

void echarts;

/** Each point is one day both sensors flew: [VIIRS cell-days, MODIS cell-days]. */
const props = defineProps<{ pairs: [number, number][]; ratio: number }>();

const option = computed(() => {
    const max = Math.max(1, ...props.pairs.map(([v]) => v));
    return {
        animation: false,
        backgroundColor: 'transparent',
        grid: { left: 52, right: 16, top: 36, bottom: 44 },
        legend: { top: 0, data: [t('Overlap days'), t('1:1 (naive merge)'), t('Fitted ratio × :r', { r: number(props.ratio, 3) })] },
        tooltip: { formatter: (p: { data: number[] }) => (p.data?.length === 2 ? `VIIRS ${number(p.data[0])} · MODIS ${number(p.data[1])}` : '') },
        xAxis: { type: 'value', name: t('VIIRS cell-days'), nameLocation: 'middle', nameGap: 28, max },
        yAxis: {
            type: 'value',
            name: t('MODIS cell-days'),
            nameLocation: 'middle',
            nameGap: 38,
            max: Math.max(1, ...props.pairs.map(([, m]) => m)) * 1.1,
        },
        series: [
            { name: t('Overlap days'), type: 'scatter', data: props.pairs, symbolSize: 5, itemStyle: { color: COLORS.harmonized, opacity: 0.55 } },
            {
                name: t('1:1 (naive merge)'),
                type: 'line',
                data: [
                    [0, 0],
                    [max, max],
                ],
                symbol: 'none',
                lineStyle: { type: 'dashed', color: '#a855f7', width: 1.5 },
            },
            {
                name: t('Fitted ratio × :r', { r: number(props.ratio, 3) }),
                type: 'line',
                data: [
                    [0, 0],
                    [max, max * props.ratio],
                ],
                symbol: 'none',
                lineStyle: { color: COLORS.modis, width: 2 },
            },
        ],
    };
});
</script>

<template>
    <VChart :theme="isDark ? 'dark' : undefined" :option="option" autoresize class="h-80 w-full" />
</template>
