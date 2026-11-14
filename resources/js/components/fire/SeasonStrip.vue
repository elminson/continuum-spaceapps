<script setup lang="ts">
import { locale, number, percent, t } from '@/lib/i18n';
import { computed } from 'vue';

export type Season = {
    months: number[];
    season: number[];
    start: number;
    peak: number;
    end: number;
    share: number;
    in_season: boolean;
    current_month: number;
    years: number;
};

const props = defineProps<{ season: Season }>();

const name = (m: number, style: 'short' | 'long' = 'long') =>
    new Intl.DateTimeFormat(locale() === 'es' ? 'es-DO' : 'en-US', { month: style }).format(new Date(2020, m - 1, 1));
const max = computed(() => Math.max(1, ...props.season.months));
</script>

<template>
    <div>
        <p class="text-sm">
            <span class="font-medium">{{
                t('Fire season: :start to :end, peak in :peak.', { start: name(season.start), end: name(season.end), peak: name(season.peak) })
            }}</span>
            <span class="text-muted-foreground">
                {{
                    t('It holds :share of a typical year’s fire (median of :years complete years).', {
                        share: percent(season.share),
                        years: season.years,
                    })
                }}
            </span>
            <span class="ml-1 whitespace-nowrap" :class="season.in_season ? 'text-orange-700 dark:text-orange-400' : 'text-muted-foreground'">
                {{ season.in_season ? t('We are in it now.') : t('Outside the season now.') }}
            </span>
        </p>
        <div class="mt-3 grid grid-cols-12 items-end gap-1" role="img" :aria-label="t('Typical fire by month')">
            <div v-for="(value, i) in season.months" :key="i" class="flex flex-col items-center gap-1">
                <div
                    class="w-full rounded-sm"
                    :class="season.season.includes(i + 1) ? 'bg-orange-600' : 'bg-muted-foreground/25'"
                    :style="{ height: `${Math.max(2, (value / max) * 56)}px` }"
                    :title="`${name(i + 1)}: ${number(value)}`"
                />
                <span class="text-[11px] tabular-nums" :class="i + 1 === season.current_month ? 'font-semibold' : 'text-muted-foreground'">{{
                    name(i + 1, 'short')
                }}</span>
            </div>
        </div>
    </div>
</template>
