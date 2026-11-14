<script setup lang="ts">
import { date, locale, number, percent, r, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import type { AlertRow } from './types';

export type Answers = {
    week: AlertRow | null;
    season: { start: number; peak: number; end: number; share: number; in_season: boolean } | null;
    compare: { from: string; to: string; times: number | null; rank: number | null; of: number } | null;
    trust: { test_days: number; improvement: number; correlation: number } | null;
    retire: { year: number; improvement: number; monthly_r: number } | null;
};

const props = defineProps<{ answers: Answers }>();
const month = (m: number) => new Intl.DateTimeFormat(locale() === 'es' ? 'es-DO' : 'en-US', { month: 'long' }).format(new Date(2020, m - 1, 1));

const items = computed(() => {
    const a = props.answers;
    const list: { q: string; big: string; text: string; href: string }[] = [];

    if (a.week)
        list.push({
            q: t('Was this week unusual?'),
            big: `×${number(a.week.times, 1)}`,
            text: t('Dominican Republic, week of :date: :times times the same week in a normal year.', {
                date: date(a.week.week_start),
                times: number(a.week.times, 1),
            }),
            href: `/areas/dominican-republic?week=${a.week.week_start}`,
        });
    if (a.season)
        list.push({
            q: t('When is the fire season, and are we in it now?'),
            big: `${month(a.season.start)}–${month(a.season.end)}`,
            text: t('Peak in :peak, :share of a typical year’s fire. :now', {
                peak: month(a.season.peak),
                share: percent(a.season.share),
                now: a.season.in_season ? t('We are in it now.') : t('Outside the season now.'),
            }),
            href: '/areas/dominican-republic',
        });
    if (a.compare)
        list.push({
            q: t('Was this period worse than usual?'),
            big: `×${number(a.compare.times, 1)}`,
            text: t('Cuba, :from to :to: rank :rank of :of years for those dates.', {
                from: date(a.compare.from),
                to: date(a.compare.to),
                rank: a.compare.rank ?? '—',
                of: a.compare.of,
            }),
            href: '/areas/cuba',
        });
    list.push({
        q: t('Where did it burn?'),
        big: t('1 km'),
        text: t('Every burning 1 km cell on NASA’s satellite image of that day, for any week since 2000.'),
        href: '/explore?place=dominican-republic',
    });
    if (a.trust)
        list.push({
            q: t('Can I trust the numbers?'),
            big: `−${percent(a.trust.improvement)}`,
            text: t('Error vs a naive merge, tested on :days days the fit never saw (:r).', {
                days: number(a.trust.test_days),
                r: r(a.trust.correlation),
            }),
            href: '/method#validation',
        });
    if (a.retire)
        list.push({
            q: t('What happens when MODIS shuts down?'),
            big: r(a.retire.monthly_r),
            text: t('Switched off in :year, rebuilt from VIIRS alone: the monthly record keeps its shape.', { year: a.retire.year }),
            href: '/areas/dominican-republic#retire',
        });
    list.push({
        q: t('Can our own system use it?'),
        big: 'JSON · CSV',
        text: t('Open API and downloads for every number on this site, no key needed.'),
        href: '/api-docs',
    });

    return list;
});
</script>

<template>
    <section>
        <h2 class="text-xl font-semibold tracking-tight">{{ t('Questions Continuum answers') }}</h2>
        <div class="mt-4 grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="item in items" :key="item.q" :href="item.href" class="group -m-3 rounded-md p-3 hover:bg-muted">
                <div class="font-medium">{{ item.q }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-orange-700 dark:text-orange-400">{{ item.big }}</div>
                <p class="mt-1 text-sm text-muted-foreground">{{ item.text }}</p>
                <span class="mt-2 inline-flex items-center gap-1 text-xs text-muted-foreground group-hover:text-foreground">
                    {{ t('See it') }} <ArrowRight class="size-3" />
                </span>
            </Link>
        </div>
    </section>
</template>
