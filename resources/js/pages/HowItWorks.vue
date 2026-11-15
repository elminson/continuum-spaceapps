<script setup lang="ts">
import OverlapScatter from '@/components/fire/OverlapScatter.vue';
import type { Fit, Status } from '@/components/fire/types';
import WeekMap from '@/components/fire/WeekMap.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { dateRange, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bot,
    Braces,
    Calculator,
    CalendarRange,
    CheckCheck,
    CloudDownload,
    Database,
    Gauge,
    Grid3x3,
    Layers,
    ListChecks,
    Server,
    ShieldCheck,
    Timer,
} from 'lucide-vue-next';
import { computed } from 'vue';

type Bulletin = { text: string; source: 'ai' | 'template'; model: string | null; facts: Record<string, unknown> };

const props = defineProps<{
    status: Status;
    evidence: { slug: string; name: string; fit: Fit | null }[];
    hispaniola: { west: number; south: number; east: number; north: number } | null;
    focusName: string | null;
    pairs: [number, number][];
    counts: { areas: number; fits: number; alerts: number; places: number; feeds: number };
    bulletin: Bulletin | null;
}>();

const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
const modis = computed(() => props.status.instruments.find((i) => i.instrument === 'modis'));
const focus = computed(() => props.evidence[0]?.fit ?? null);
const bbox = computed(() =>
    props.hispaniola
        ? [props.hispaniola.west, props.hispaniola.south, props.hispaniola.east, props.hispaniola.north]
        : [-72.01, 17.47, -68.32, 19.93],
);

const stages = computed(() => [
    {
        icon: CloudDownload,
        title: t('Fetch'),
        code: 'FirmsFeed · fire:ingest',
        body: t(
            'Every hour the NASA FIRMS 24-hour feeds for MODIS, NOAA-20 and NOAA-21 are pulled and clipped to the areas; the 2000→2026 archive was loaded once.',
        ),
        num: number(props.counts.feeds),
        unit: t('NASA feeds, every hour'),
    },
    {
        icon: ListChecks,
        title: t('Parse and deduplicate'),
        code: 'FirmsFeed::import',
        body: t(
            'Each CSV row becomes a detection. The same pixel from the same satellite at the same minute is one row, so overlapping pulls never count twice.',
        ),
        num: number(props.status.detections),
        unit: t('fire pixels from NASA'),
    },
    {
        icon: Grid3x3,
        title: t('Snap to one grid'),
        code: 'BuildGrid · BuildAreaDays',
        body: t(
            'Pixels of both sensors land on the same 0.01° grid (about 1 km). The unit becomes the cell-day, with every satellite of a sensor merged.',
        ),
        num: number(props.status.cell_days),
        unit: t('1 km cell-days'),
    },
    {
        icon: Calculator,
        title: t('Calibrate on the overlap'),
        code: 'Calibrate::fit',
        body: t(
            'On the days both sensors flew, MODIS ÷ VIIRS is fitted for the whole year and for each month, with a 200-sample bootstrap interval.',
        ),
        num: number(props.counts.fits),
        unit: t('fitted factors'),
    },
    {
        icon: CheckCheck,
        title: t('Validate before publishing'),
        code: 'Calibrate::validate',
        body: t(
            'Every fourth overlap day is held out. The ratio is fitted without it and scored on it, next to a naive merge, so the gain is measured, not claimed.',
        ),
        num: focus.value?.validation ? `−${percent(focus.value.validation.improvement)}` : '—',
        unit: t('error vs naive merge (:area)', { area: props.focusName ?? '' }),
    },
    {
        icon: CalendarRange,
        title: t('Calendar, season and unusual weeks'),
        code: 'Harmonizer · FireSeason · DetectAlerts',
        body: t(
            'One record on the MODIS or VIIRS scale; the fire season from the monthly medians; each week against the same week in every other year (median and MAD).',
        ),
        num: number(props.counts.alerts),
        unit: t('unusual weeks flagged'),
    },
    {
        icon: Bot,
        title: t('Write it in plain words'),
        code: 'Bulletin',
        body: t(
            'Claude or a self-hosted open model turns the computed facts into a two-line bulletin in Spanish or English. Any number it writes that is not a fact sends it back to a template.',
        ),
        num: '0',
        unit: t('invented numbers shown'),
    },
    {
        icon: Braces,
        title: t('Serve, with the method'),
        code: 'PageController · AreaApiController',
        body: t('The same numbers drive these pages, the CSV downloads and a public JSON API, in English and Spanish, with no login.'),
        num: number(props.counts.places + props.counts.areas),
        unit: t('areas and ready-made places'),
    },
]);

const architecture = [
    {
        title: 'NASA',
        items: [
            ['NASA FIRMS', 'MODIS C6.1 and VIIRS 375 m fire detections, archive and hourly 24 h feeds'],
            ['NASA GIBS', 'Blue Marble and the MODIS Terra image of the day under every map'],
            ['NASA POWER', 'Daily temperature, humidity, rain and wind at each area’s centre since 2000: the drivers behind the next-week forecast'],
        ],
    },
    {
        title: 'Ingest',
        items: [
            ['Scheduler', 'Pulls the feeds every hour, re-processes recent days, precomputes pages'],
            ['Import', 'CSV to detections, one row per pixel, duplicates ignored'],
        ],
    },
    { title: 'Store', items: [['PostgreSQL', 'Detections, 1 km cell-days, daily area totals, fits, unusual weeks']] },
    {
        title: 'Harmonize',
        items: [
            ['Fit and test', 'Monthly ratio, bootstrap interval, held-out score'],
            ['Claude · Gemma 4', 'Writes the bulletin and the situation reading from computed facts, numbers checked'],
        ],
    },
    {
        title: 'Serve',
        items: [
            ['Laravel + Vue', 'Maps, calendars, season, compare, retirement test, EN/ES'],
            ['Public API', 'JSON and CSV for every number'],
        ],
    },
];

const running = [
    {
        icon: Gauge,
        title: 'Heavy numbers are computed ahead',
        body: 'Totals over 21 million rows are worked out after each hourly update and stored, so no visitor waits on them.',
        metric: '0.1–0.4 s',
        note: 'typical page load on the live server',
    },
    {
        icon: Database,
        title: 'Indexes shaped like the questions',
        body: 'The grid is indexed by place and day, so a week map or a drawn box reads only the cells it needs.',
        metric: String(props.counts.places),
        note: 'ready-made places precomputed every hour',
    },
    {
        icon: Timer,
        title: 'Fail-safe AI',
        body: 'The bulletin waits at most 20 seconds for the model; past that, or with any unknown number, the template answers.',
        metric: '20 s',
        note: 'longest wait for the model',
    },
    {
        icon: ShieldCheck,
        title: 'Tested on every change',
        body: 'Unit and feature tests cover the grid, the fit, the alerts, the bulletin guard, the API and every page.',
        metric: '44',
        note: 'automated tests',
    },
];
</script>

<template>
    <Head :title="t('How it works')" />
    <SiteLayout>
        <section class="grid items-center gap-8 lg:grid-cols-[1fr_1.15fr]">
            <div>
                <p class="text-sm font-medium text-orange-700 dark:text-orange-400">{{ t('How it works') }}</p>
                <h1 class="mt-2 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">
                    {{ t('Two satellites. One record you can check.') }}
                </h1>
                <p class="mt-4 text-muted-foreground">
                    {{ t('The pipeline, the evidence and the machine behind Continuum. Every number on this page is read live from the system.') }}
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-4 text-sm">
                    <a href="#pipeline" class="inline-flex items-center gap-1.5 rounded-md bg-foreground px-3 py-2 text-background"
                        >{{ t('See the pipeline') }} <ArrowRight class="size-4"
                    /></a>
                    <Link href="/method" class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">{{
                        t('Read the method')
                    }}</Link>
                </div>
            </div>
            <WeekMap height="h-[24rem]" :bbox="bbox" :days="30" />
        </section>

        <dl class="mt-12 grid gap-6 border-y py-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dd class="text-3xl font-semibold tabular-nums">{{ number(status.detections) }}</dd>
                <dt class="text-sm text-muted-foreground">{{ t('NASA FIRMS detections') }}</dt>
            </div>
            <div>
                <dd class="text-3xl font-semibold tabular-nums">
                    {{ Math.floor(status.record_days / 365.25) }} <span class="text-base font-normal text-muted-foreground">{{ t('years') }}</span>
                </dd>
                <dt class="text-sm text-muted-foreground">{{ dateRange(status.first_day, status.last_day) }}</dt>
            </div>
            <div>
                <dd class="text-3xl font-semibold tabular-nums">{{ status.satellites.length }}</dd>
                <dt class="text-sm text-muted-foreground">{{ t('Satellites, two sensors') }}</dt>
            </div>
            <div>
                <dd class="text-3xl font-semibold tabular-nums">
                    ×{{ number(viirs?.pixels_per_cell, 1) }}
                    <span class="text-base font-normal text-muted-foreground">/ ×{{ number(modis?.pixels_per_cell, 1) }}</span>
                </dd>
                <dt class="text-sm text-muted-foreground">{{ t('VIIRS / MODIS pixels per 1 km cell, before harmonizing') }}</dt>
            </div>
        </dl>

        <section id="pipeline" class="mt-16 scroll-mt-6">
            <h2 class="text-2xl font-semibold tracking-tight">{{ t('From a CSV row to a calendar you can trust') }}</h2>
            <p class="mt-2 max-w-3xl text-muted-foreground">
                {{ t('Eight stages, each one a class you can read in the repository, each showing what it has done so far.') }}
            </p>
            <ol class="mt-8">
                <li v-for="(s, i) in stages" :key="i" class="relative grid gap-x-6 gap-y-2 pb-8 pl-12 last:pb-0 md:grid-cols-[14rem_1fr_9rem]">
                    <span v-if="i < stages.length - 1" class="absolute left-[15px] top-8 h-full w-px bg-orange-300 dark:bg-orange-800" />
                    <span
                        class="absolute left-0 top-0 grid size-8 place-items-center rounded-full border border-orange-300 bg-background text-orange-600 dark:border-orange-800"
                    >
                        <component :is="s.icon" class="size-4" />
                    </span>
                    <div>
                        <div class="font-medium">{{ s.title }}</div>
                        <div class="font-mono text-xs text-muted-foreground">{{ s.code }}</div>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ s.body }}</p>
                    <div class="md:text-right">
                        <div class="text-xl font-semibold tabular-nums">{{ s.num }}</div>
                        <div class="text-xs text-muted-foreground">{{ s.unit }}</div>
                    </div>
                </li>
            </ol>
        </section>

        <section class="mt-16">
            <h2 class="text-2xl font-semibold tracking-tight">{{ t('The evidence, not the claim') }}</h2>
            <p class="mt-2 max-w-3xl text-muted-foreground">
                {{
                    t(
                        ':area has the longest shared record of the areas. Each dot is a day both sensors flew; the dashed line is what a naive merge assumes.',
                        { area: focusName ?? '' },
                    )
                }}
            </p>
            <div class="mt-6 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <div class="rounded-md border p-4">
                    <div class="text-sm font-medium">{{ t('Same day, both sensors') }}</div>
                    <OverlapScatter v-if="focus" :pairs="pairs" :ratio="focus.ratio" />
                </div>
                <div
                    v-if="focus?.validation"
                    class="flex flex-col justify-between rounded-md border border-orange-300/60 bg-orange-50/60 p-5 dark:border-orange-900 dark:bg-orange-950/20"
                >
                    <div>
                        <div class="text-sm font-medium text-orange-700 dark:text-orange-400">{{ t('On held-out days') }}</div>
                        <div class="mt-2 text-6xl font-semibold tabular-nums">−{{ percent(focus.validation.improvement) }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">{{ t('less daily error than merging the two records naively.') }}</p>
                    </div>
                    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm tabular-nums">
                        <div>
                            <dt class="text-muted-foreground">{{ t('Daily error') }}</dt>
                            <dd class="font-medium">
                                {{ number(focus.validation.mae, 1) }}
                                <span class="text-muted-foreground">vs {{ number(focus.validation.naive_mae, 1) }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Correlation') }}</dt>
                            <dd class="font-medium">{{ r(focus.validation.correlation) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Fitted on') }}</dt>
                            <dd class="font-medium">{{ t(':n days', { n: number(focus.validation.train_days) }) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Scored on') }}</dt>
                            <dd class="font-medium">{{ t(':n days', { n: number(focus.validation.test_days) }) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 font-normal">{{ t('Area') }}</th>
                            <th class="text-right font-normal">MODIS ÷ VIIRS</th>
                            <th class="text-right font-normal">{{ t('Held-out days') }}</th>
                            <th class="text-right font-normal">{{ t('Error vs naive merge') }}</th>
                            <th class="text-right font-normal">{{ t('Correlation') }}</th>
                        </tr>
                    </thead>
                    <tbody class="tabular-nums">
                        <tr v-for="e in evidence" :key="e.slug" class="border-t">
                            <td class="py-2">
                                <Link :href="`/areas/${e.slug}`" class="hover:underline">{{ e.name }}</Link>
                            </td>
                            <td class="text-right">{{ e.fit ? `×${number(e.fit.ratio, 3)}` : '—' }}</td>
                            <td class="text-right">{{ number(e.fit?.validation?.test_days) }}</td>
                            <td class="text-right font-medium">{{ e.fit?.validation ? `−${percent(e.fit.validation.improvement)}` : '—' }}</td>
                            <td class="text-right">{{ r(e.fit?.validation?.correlation) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="bulletin" class="mt-16">
            <p class="text-sm font-medium text-orange-700 dark:text-orange-400">{{ t('Responsible AI') }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight">{{ t('AI that writes, never decides') }}</h2>
            <p class="mt-2 max-w-3xl text-muted-foreground">
                {{
                    t(
                        'The statistics decide whether a week is unusual. The model (Claude, or Gemma 4 on our own server) only turns those facts into two plain sentences, and its text is checked number by number before anyone sees it.',
                    )
                }}
            </p>
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="rounded-md border">
                    <div class="flex justify-between border-b px-4 py-2 text-xs text-muted-foreground">
                        <span>{{ t('What the model receives') }}</span
                        ><span class="font-mono">facts.json</span>
                    </div>
                    <pre class="max-h-80 overflow-auto px-4 py-3 font-mono text-xs leading-relaxed">{{
                        JSON.stringify(bulletin.facts, null, 2)
                    }}</pre>
                </div>
                <div class="flex flex-col gap-4">
                    <div class="rounded-md border">
                        <div class="flex justify-between border-b px-4 py-2 text-xs text-muted-foreground">
                            <span>{{ t('What it returns') }}</span
                            ><span>{{ bulletin.source === 'ai' ? bulletin.model : t('template') }}</span>
                        </div>
                        <p class="px-4 py-3 text-[15px] leading-relaxed">{{ bulletin.text }}</p>
                    </div>
                    <ul class="grid gap-3 text-sm sm:grid-cols-2">
                        <li class="flex gap-2">
                            <Calculator class="mt-0.5 size-4 shrink-0 text-orange-600" /><span
                                ><b class="font-medium">{{ t('Statistics decide') }}.</b>
                                <span class="text-muted-foreground">{{ t('The model never flags or changes a number.') }}</span></span
                            >
                        </li>
                        <li class="flex gap-2">
                            <CheckCheck class="mt-0.5 size-4 shrink-0 text-orange-600" /><span
                                ><b class="font-medium">{{ t('Every number checked') }}.</b>
                                <span class="text-muted-foreground">{{ t('One unknown figure and the template answers.') }}</span></span
                            >
                        </li>
                        <li class="flex gap-2">
                            <Bot class="mt-0.5 size-4 shrink-0 text-orange-600" /><span
                                ><b class="font-medium">{{ t('Two writers, one rule') }}.</b>
                                <span class="text-muted-foreground">{{
                                    t('Claude (Anthropic API) or Gemma 4 on our own Ollama server; each is the fallback for the other.')
                                }}</span></span
                            >
                        </li>
                        <li class="flex gap-2">
                            <ShieldCheck class="mt-0.5 size-4 shrink-0 text-orange-600" /><span
                                ><b class="font-medium">{{ t('Labelled') }}.</b>
                                <span class="text-muted-foreground">{{ t('Each bulletin says whether AI or the template wrote it.') }}</span></span
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="mt-16 rounded-md border bg-muted/40 p-6">
            <h2 class="text-2xl font-semibold tracking-tight">{{ t('One service, end to end') }}</h2>
            <p class="mt-2 text-muted-foreground">{{ t('Open NASA data in, a validated record out, all on one small Linux server.') }}</p>
            <div class="mt-6 grid gap-4 md:grid-cols-5">
                <div v-for="col in architecture" :key="col.title">
                    <div class="mb-2 text-xs font-medium text-muted-foreground">{{ t(col.title) }}</div>
                    <div class="flex flex-col gap-2">
                        <div v-for="[name, body] in col.items" :key="name" class="rounded-md border bg-background p-3">
                            <div class="flex items-center gap-1.5 text-sm font-medium"><Layers class="size-3.5 text-orange-600" />{{ t(name) }}</div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ t(body) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-4 inline-flex items-center gap-2 text-xs text-muted-foreground">
                <Server class="size-3.5" /> {{ t('GitHub → Spinnr → Nginx, PHP and PostgreSQL; the scheduler runs every hour.') }}
            </p>
        </section>

        <section class="mt-16">
            <h2 class="text-2xl font-semibold tracking-tight">{{ t('Built to keep running') }}</h2>
            <div class="mt-6 grid gap-8 md:grid-cols-2">
                <div v-for="item in running" :key="item.title" class="flex gap-3">
                    <component :is="item.icon" class="mt-0.5 size-5 shrink-0 text-orange-600" />
                    <div>
                        <div class="font-medium">{{ t(item.title) }}</div>
                        <p class="mt-1 text-sm text-muted-foreground">{{ t(item.body) }}</p>
                        <div class="mt-2 font-mono text-lg font-semibold tabular-nums">{{ item.metric }}</div>
                        <div class="text-xs text-muted-foreground">{{ t(item.note) }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="mt-16 flex flex-wrap items-center justify-between gap-4 rounded-md border border-orange-300/60 bg-orange-50/60 p-6 dark:border-orange-900 dark:bg-orange-950/20"
        >
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">{{ t('Every number here is an API call away.') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('Calendars, fits, unusual weeks, bulletins and fire maps for every area, open and documented.') }}
                </p>
            </div>
            <Link href="/api-docs" class="inline-flex items-center gap-1.5 rounded-md bg-foreground px-3 py-2 text-sm text-background"
                >{{ t('Explore the API') }} <ArrowRight class="size-4"
            /></Link>
        </section>
    </SiteLayout>
</template>
