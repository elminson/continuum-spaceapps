<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';
import { ChevronDown, Play } from 'lucide-vue-next';
import { reactive } from 'vue';

const base = typeof window === 'undefined' ? '' : window.location.origin;

type Endpoint = { path: string; what: string; params?: [string, string][]; returns: string; csv?: boolean };

const groups: { title: string; items: Endpoint[] }[] = [
    {
        title: 'Areas and their record',
        items: [
            {
                path: '/api/areas',
                what: 'The areas of interest with their bounding boxes and the date their record was last processed.',
                returns: 'data[]: slug, name, bbox [west, south, east, north], processed_at',
            },
            {
                path: '/api/areas/dominican-republic/monthly?scale=modis',
                what: 'The harmonized monthly record of an area: both sensors, the harmonized value, its interval, and the MODIS/VIIRS fit with its held-out test.',
                params: [['scale', 'modis (default) or viirs: which sensor the harmonized value is expressed in']],
                returns:
                    'scale, fit {ratio, low, high, days, validation {mae, naive_mae, improvement, correlation, train_days, test_days}, months[]}, data[]: month, modis, viirs, value, low, high, estimated',
            },
            {
                path: '/api/areas/dominican-republic/monthly.csv?scale=viirs',
                what: 'The same monthly record as a CSV file, with the method and the fitted ratio in the header comment.',
                params: [['scale', 'modis or viirs']],
                returns: 'month, modis_cell_days, viirs_cell_days, harmonized, low, high, estimated',
                csv: true,
            },
            {
                path: '/api/areas/dominican-republic/season',
                what: 'The fire season of an area from its monthly medians: start, peak and end month, its share of the year, and whether today is inside it.',
                returns: 'data: season[] (month numbers), start, peak, end, share, in_season',
            },
        ],
    },
    {
        title: 'Compare and detect',
        items: [
            {
                path: '/api/areas/cuba/compare?from=2026-08-10&to=2026-08-31',
                what: 'Any date range against the same dates in every other year: the value, the typical value (median of the other years), times normal and the rank among years. Up to 366 days.',
                params: [['from, to', 'YYYY-MM-DD; a range that runs past the last ingested day is compared on the days that exist (see "through")']],
                returns: 'data: from, to, days, through, value, typical, times, rank, of, years[] {year, from, value}',
            },
            {
                path: '/api/areas/cuba/alerts',
                what: 'The unusual weeks of an area: each week scored against the same week in every other year (median and MAD), strongest first.',
                returns: 'data[]: week_start, observed, typical, times, score, severity (elevated | high | extreme)',
            },
            {
                path: '/api/areas/dominican-republic/bulletin?locale=en',
                what: 'The plain-language bulletin of an area, written by AI from the computed facts and checked number by number, or a fixed template. With ?week it describes that unusual week; without it, the last 30 days.',
                params: [
                    ['locale', 'en or es'],
                    ['week', 'YYYY-MM-DD, the Monday of a flagged week (optional)'],
                ],
                returns: 'data: text, source (ai | template), model, facts',
            },
        ],
    },
    {
        title: 'Forecast and the future of MODIS',
        items: [
            {
                path: '/api/areas/dominican-republic/forecast',
                what: 'Next week’s outlook of an area from a ridge regression on this record and NASA POWER weather, with its rolling validation against climatology and persistence. Computed once a day.',
                returns:
                    'data: week_start, expected, low, high, climatology, times_normal, last_week, weather_through, validation {weeks, from, to, mae, mae_climatology, mae_persistence, skill_vs_climatology, skill_vs_persistence, correlation}, weights',
            },
            {
                path: '/api/areas/cuba/retirement?year=2024',
                what: 'The MODIS retirement test: the ratio fitted only before the cutoff year, later months rebuilt from VIIRS alone and scored against the real MODIS record.',
                params: [['year', 'the cutoff year (default 2024)']],
                returns: 'data: cutoff, daily {mae, naive_mae, improvement}, monthly {correlation, bias}, months[]',
            },
        ],
    },
    {
        title: 'Any box and its fires',
        items: [
            {
                path: '/api/explore?bbox=-72.01,17.47,-68.32,19.93',
                what: 'Instant analysis of any box inside the areas: its own MODIS/VIIRS fit with a held-out test, its monthly record and its unusual weeks.',
                params: [['bbox', 'west,south,east,north in degrees; at most 8° on each side, inside one of the areas']],
                returns: 'data: available, bbox, fit, monthly[], alerts[]',
            },
            {
                path: '/api/week?bbox=-84.6,18.7,-74.2,24.9&week=2026-08-17',
                what: 'GeoJSON of the 1 km cells with fire in one week (or the last N days) inside a box, with the sensors that saw each cell, its detections, radiative power and the day it first burned.',
                params: [
                    ['bbox', 'west,south,east,north'],
                    ['week', 'YYYY-MM-DD, any day of the week'],
                    ['days', '1–31, the last N days instead of a week'],
                    ['format', 'csv for a file download'],
                ],
                returns: 'FeatureCollection: week, from, to, days, features[] {geometry Point, properties {sensors, detections, frp, day}}',
            },
            {
                path: '/api/week?bbox=-72.01,17.47,-68.32,19.93&week=2015-04-27&format=csv',
                what: 'The same cells as a CSV file.',
                returns: 'latitude, longitude, first_day, sensors, detections, frp_mw',
                csv: true,
            },
            {
                path: '/api/areas/dominican-republic/week?week=2015-04-27',
                what: 'The same for a whole area by slug.',
                params: [
                    ['week or days', 'as above'],
                    ['format', 'csv'],
                ],
                returns: 'FeatureCollection as above',
            },
        ],
    },
];

const samples = reactive<Record<string, { loading: boolean; text: string | null; status: number | null }>>({});

async function tryIt(e: Endpoint) {
    const s = (samples[e.path] ??= { loading: false, text: null, status: null });
    if (s.text !== null) {
        s.text = null;
        return;
    }
    s.loading = true;
    try {
        const r = await fetch(e.path, { headers: { Accept: e.csv ? 'text/csv' : 'application/json' } });
        s.status = r.status;
        const body = await r.text();
        s.text = e.csv
            ? body.split('\n').slice(0, 8).join('\n')
            : JSON.stringify(JSON.parse(body), null, 2).slice(0, 1400) + (body.length > 1400 ? '\n…' : '');
    } catch (err) {
        s.text = String(err);
    } finally {
        s.loading = false;
    }
}
</script>

<template>
    <Head title="API" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Public API') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Read-only JSON and CSV. No key, no login, 120 requests per minute. Every number on this site comes from these endpoints; "Try it" runs the call from your browser and shows the beginning of the answer.',
                )
            }}
        </p>

        <section v-for="g in groups" :key="g.title" class="mt-8">
            <h2 class="font-semibold">{{ t(g.title) }}</h2>
            <ul class="mt-3 divide-y rounded-lg border">
                <li v-for="e in g.items" :key="e.path" class="px-4 py-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <a :href="e.path" target="_blank" class="break-all font-mono text-sm text-orange-700 hover:underline dark:text-orange-400"
                            >GET {{ base }}{{ e.path }}</a
                        >
                        <button class="inline-flex shrink-0 items-center gap-1 rounded-md border px-2 py-1 text-xs hover:bg-muted" @click="tryIt(e)">
                            <component :is="samples[e.path]?.text ? ChevronDown : Play" class="size-3" />
                            {{ samples[e.path]?.loading ? t('Loading…') : samples[e.path]?.text ? t('Hide') : t('Try it') }}
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t(e.what) }}</p>
                    <dl v-if="e.params" class="mt-2 grid gap-x-4 gap-y-1 text-xs sm:grid-cols-[8rem_1fr]">
                        <template v-for="[name, meaning] in e.params" :key="name">
                            <dt class="font-mono">{{ name }}</dt>
                            <dd class="text-muted-foreground">{{ t(meaning) }}</dd>
                        </template>
                    </dl>
                    <p class="mt-2 text-xs text-muted-foreground">
                        <span class="font-medium text-foreground">{{ t('Returns') }}:</span> <span class="font-mono">{{ e.returns }}</span>
                    </p>
                    <pre
                        v-if="samples[e.path]?.text"
                        class="mt-3 max-h-80 overflow-auto rounded-md bg-muted/60 p-3 font-mono text-xs leading-relaxed"
                        >{{ samples[e.path].text }}</pre
                    >
                </li>
            </ul>
        </section>

        <p class="mt-8 text-xs text-muted-foreground">
            {{
                t(
                    'Data: NASA FIRMS (MODIS C6.1, VIIRS 375 m), NASA POWER weather and NASA GIBS imagery, processed by Continuum. Cite the site and NASA FIRMS when you reuse the numbers.',
                )
            }}
        </p>
    </SiteLayout>
</template>
