<script setup lang="ts">
import BulletinCard from '@/components/fire/BulletinCard.vue';
import CalendarHeatmap from '@/components/fire/CalendarHeatmap.vue';
import ComparePanel from '@/components/fire/ComparePanel.vue';
import MonthlyChart from '@/components/fire/MonthlyChart.vue';
import Pager from '@/components/fire/Pager.vue';
import RetirementPanel from '@/components/fire/RetirementPanel.vue';
import SeasonStrip, { type Season } from '@/components/fire/SeasonStrip.vue';
import TimesNormal from '@/components/fire/TimesNormal.vue';
import WeekMap from '@/components/fire/WeekMap.vue';
import type { AlertRow, Fit, Month } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    area: { slug: string; name: string; bbox: number[]; processed_at: string | null };
    scale: 'modis' | 'viirs';
    monthly: Month[];
    fit: Fit | null;
    alerts: AlertRow[];
    week?: string | null;
    season: Season | null;
}>();

const week = ref<string | null>(props.week ?? props.alerts[0]?.week_start ?? null);

function pickWeek(start: string) {
    week.value = start;
    document.getElementById('map')?.scrollIntoView({ behavior: 'smooth' });
}
const PER_PAGE = 15;
const page = ref(1);
const pages = computed(() => Math.max(1, Math.ceil(props.alerts.length / PER_PAGE)));

type SortKey = 'week_start' | 'observed' | 'typical' | 'times';
const sortKey = ref<SortKey>('times');
const sortDesc = ref(true);
const columns: { key: SortKey; label: string; align: string }[] = [
    { key: 'week_start', label: 'Week of', align: 'text-left' },
    { key: 'observed', label: 'Observed', align: 'text-left' },
    { key: 'typical', label: 'Typical', align: 'text-left' },
    { key: 'times', label: 'Compared with the same week in other years', align: 'text-right' },
];

function sortBy(key: SortKey) {
    sortDesc.value = sortKey.value === key ? !sortDesc.value : key !== 'typical';
    sortKey.value = key;
    page.value = 1;
}

const sorted = computed(() => {
    const dir = sortDesc.value ? -1 : 1;
    return [...props.alerts].sort((a, b) => {
        const x = a[sortKey.value] ?? 0;
        const y = b[sortKey.value] ?? 0;
        return (x < y ? -1 : x > y ? 1 : 0) * dir;
    });
});
const shown = computed(() => sorted.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));
</script>

<template>
    <Head :title="area.name" />
    <SiteLayout>
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <Link href="/" class="text-sm text-muted-foreground hover:text-foreground">← {{ t('Areas') }}</Link>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ area.name }}</h1>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-muted-foreground">{{ t('Scale') }}</span>
                <Link
                    v-for="s in ['modis', 'viirs']"
                    :key="s"
                    :href="`/areas/${area.slug}?scale=${s}`"
                    preserve-scroll
                    class="rounded-md border px-2 py-1"
                    :class="scale === s ? 'bg-foreground text-background' : 'hover:bg-muted'"
                    >{{ s.toUpperCase() }}</Link
                >
                <a :href="`/api/areas/${area.slug}/monthly.csv?scale=${scale}`" class="rounded-md border px-2 py-1 hover:bg-muted">CSV</a>
            </div>
        </header>

        <section id="map" class="mt-5 scroll-mt-4">
            <WeekMap
                height="h-[55vh] min-h-[24rem]"
                :week="week"
                :days="30"
                :bbox="area.bbox"
                :times="alerts.find((a) => a.week_start === week)?.times ?? null"
            />
            <p class="mt-2 text-xs text-muted-foreground">{{ t('Click a week under Unusual weeks to map it here.') }}</p>
        </section>

        <dl v-if="fit?.validation" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('MODIS ÷ VIIRS cell-days') }}</dt>
                <dd class="text-2xl font-semibold tabular-nums">×{{ number(fit.ratio, 3) }}</dd>
                <dd class="text-xs text-muted-foreground">
                    {{ t('95% interval :low–:high', { low: number(fit.low, 3), high: number(fit.high, 3) }) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Error vs naive merge') }}</dt>
                <dd class="text-2xl font-semibold tabular-nums text-orange-700 dark:text-orange-400">−{{ percent(fit.validation.improvement) }}</dd>
                <dd class="text-xs text-muted-foreground">{{ t('on :days held-out days', { days: number(fit.validation.test_days) }) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Daily error') }}</dt>
                <dd class="text-2xl font-semibold tabular-nums">{{ number(fit.validation.mae, 1) }}</dd>
                <dd class="text-xs text-muted-foreground">
                    {{ t('naive merge: :naive cell-days', { naive: number(fit.validation.naive_mae, 1) }) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Correlation on held-out days') }}</dt>
                <dd class="text-2xl font-semibold tabular-nums">{{ r(fit.validation.correlation) }}</dd>
                <dd class="text-xs text-muted-foreground">
                    {{ t('overlap :from → :to', { from: fit.validation.overlap_from, to: fit.validation.overlap_to }) }}
                </dd>
            </div>
        </dl>

        <BulletinCard id="bulletin" class="mt-6 scroll-mt-6" :slug="area.slug" :week="week" />

        <div class="mt-10 grid gap-10 lg:grid-cols-2">
            <section v-if="season" id="season" class="scroll-mt-6">
                <h2 class="text-lg font-semibold">{{ t('Fire season') }}</h2>
                <SeasonStrip class="mt-2" :season="season" />
            </section>
            <section id="compare" class="scroll-mt-6">
                <h2 class="text-lg font-semibold">{{ t('Compare any dates') }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ t('Pick a period: it is set against the same dates in every other year since 2000.') }}
                </p>
                <ComparePanel class="mt-3" :slug="area.slug" />
            </section>
        </div>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">{{ t('Fire calendar') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{ t('Active 1 km cell-days per month on the :scale scale. Each row is a year.', { scale: scale.toUpperCase() }) }}
            </p>
            <CalendarHeatmap class="mt-3" :months="monthly" />
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">{{ t('The two sensors, and the harmonized record') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{ t('Dashed: each sensor as it is. Green: one continuous record; shaded where a value was converted from the other sensor.') }}
            </p>
            <MonthlyChart class="mt-3" :months="monthly" :scale="scale" />
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">{{ t('Unusual weeks') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{ t('Weeks far above the same week in every other year (median and MAD, MODIS scale).') }}
            </p>
            <table v-if="alerts.length" class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th
                            v-for="c in columns"
                            :key="c.key"
                            class="py-2 font-normal"
                            :class="c.align"
                            :aria-sort="sortKey === c.key ? (sortDesc ? 'descending' : 'ascending') : 'none'"
                        >
                            <button
                                class="inline-flex items-center gap-1 hover:text-foreground"
                                :class="sortKey === c.key ? 'font-medium text-foreground' : ''"
                                @click="sortBy(c.key)"
                            >
                                {{ t(c.label) }}
                                <span class="text-xs" :class="sortKey === c.key ? 'opacity-100' : 'opacity-30'">{{
                                    sortKey === c.key && !sortDesc ? '▲' : '▼'
                                }}</span>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="a in shown"
                        :key="a.week_start"
                        class="cursor-pointer tabular-nums hover:bg-muted/50"
                        :class="{ 'bg-muted': a.week_start === week }"
                        @click="pickWeek(a.week_start)"
                    >
                        <td class="py-2">{{ date(a.week_start) }}</td>
                        <td>{{ number(a.observed) }}</td>
                        <td>{{ number(a.typical) }}</td>
                        <td class="text-right"><TimesNormal :times="a.times" :severity="a.severity" /></td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="mt-3 text-sm text-muted-foreground">{{ t('No unusual weeks.') }}</p>
            <Pager :page="page" :pages="pages" :total="alerts.length" :per-page="PER_PAGE" @go="(p) => (page = p)" />
        </section>

        <section id="retire" class="mt-8 scroll-mt-6">
            <h2 class="text-lg font-semibold">{{ t('When MODIS retires') }}</h2>
            <p class="max-w-3xl text-sm text-muted-foreground">
                {{
                    t(
                        'NASA plans to stop MODIS around January 2027. Pick a year: the ratio is fitted on earlier days only, every later month is rebuilt from VIIRS alone, and the result is checked against the MODIS record that really exists.',
                    )
                }}
            </p>
            <RetirementPanel class="mt-3" :slug="area.slug" />
        </section>
    </SiteLayout>
</template>
