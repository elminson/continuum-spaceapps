<script setup lang="ts">
import CalendarHeatmap from '@/components/fire/CalendarHeatmap.vue';
import Kpi from '@/components/fire/Kpi.vue';
import MonthlyChart from '@/components/fire/MonthlyChart.vue';
import Pager from '@/components/fire/Pager.vue';
import RetirementPanel from '@/components/fire/RetirementPanel.vue';
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
}>();

const week = ref<string | null>(props.week ?? props.alerts[0]?.week_start ?? null);
const PER_PAGE = 15;
const page = ref(1);
const pages = computed(() => Math.max(1, Math.ceil(props.alerts.length / PER_PAGE)));
const shown = computed(() => props.alerts.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));
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

        <div v-if="fit?.validation" class="mt-6 grid gap-3 sm:grid-cols-4">
            <Kpi
                :label="t('MODIS ÷ VIIRS cell-days')"
                :value="`×${number(fit.ratio, 3)}`"
                :hint="t('95% interval :low–:high', { low: number(fit.low, 3), high: number(fit.high, 3) })"
            />
            <Kpi
                :label="t('Error vs naive merge')"
                :value="`−${percent(fit.validation.improvement)}`"
                :hint="t('on :days held-out days', { days: number(fit.validation.test_days) })"
            />
            <Kpi
                :label="t('Daily error')"
                :value="number(fit.validation.mae, 1)"
                :hint="t('naive merge: :naive cell-days', { naive: number(fit.validation.naive_mae, 1) })"
            />
            <Kpi
                :label="t('Correlation on held-out days')"
                :value="r(fit.validation.correlation)"
                :hint="t('overlap :from → :to', { from: fit.validation.overlap_from, to: fit.validation.overlap_to })"
            />
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
                {{ t('Weeks far above the same week in every other year (median and MAD, MODIS scale). Click a week to map its fires.') }}
            </p>
            <table v-if="alerts.length" class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-normal">{{ t('Week of') }}</th>
                        <th class="font-normal">{{ t('Observed') }}</th>
                        <th class="font-normal">{{ t('Typical') }}</th>
                        <th class="font-normal">{{ t('Times normal') }}</th>
                        <th class="font-normal">{{ t('Severity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="a in shown"
                        :key="a.week_start"
                        class="cursor-pointer tabular-nums hover:bg-muted/50"
                        :class="{ 'bg-muted': a.week_start === week }"
                        @click="week = a.week_start"
                    >
                        <td class="py-2">{{ date(a.week_start) }}</td>
                        <td>{{ number(a.observed) }}</td>
                        <td>{{ number(a.typical) }}</td>
                        <td>×{{ number(a.times, 1) }}</td>
                        <td>{{ t(a.severity) }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="mt-3 text-sm text-muted-foreground">{{ t('No unusual weeks.') }}</p>
            <Pager :page="page" :pages="pages" :total="alerts.length" :per-page="PER_PAGE" @go="(p) => (page = p)" />
            <WeekMap v-if="week" class="mt-4" :week="week" :bbox="area.bbox" />
        </section>

        <section class="mt-8">
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
