<script setup lang="ts">
import type { AlertRow, Fit, Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, dateRange, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, Check, FileText, Github, PlayCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    status: Status;
    focus: { slug: string; name: string; fit: Fit | null; top: AlertRow | null } | null;
    counts: { areas: number; alerts: number; places: number };
    impact: {
        focus_weeks: Record<string, number>;
        focus_weeks_5y: number;
        all_weeks: number;
        subscriptions: number;
        km2: number;
        record_years: number;
    };
}>();

const fit = computed(() => props.focus?.fit ?? null);
const area = computed(() => `/areas/${props.focus?.slug ?? 'dominican-republic'}`);
const topWeek = computed(() => (props.focus?.top ? `${area.value}?week=${props.focus.top.week_start}` : area.value));
const years = computed(() => Math.floor(props.status.record_days / 365.25));

const resources = [
    { href: 'https://youtu.be/ypfUgrSXI0Y', label: '30-second demo (English)', icon: PlayCircle },
    { href: 'https://youtu.be/D-HypfIguYk', label: 'Full explanation (Spanish, English subtitles, 4:16)', icon: PlayCircle },
    { href: '/slides/continuum-slides.pdf', label: '7-slide deck (PDF)', icon: FileText },
    { href: 'https://github.com/elminson/continuum-spaceapps', label: 'Code on GitHub', icon: Github },
    { href: 'https://www.spaceappschallenge.org/2026/find-a-team/continuum1/', label: 'NASA team page', icon: ArrowUpRight },
    { href: '/questions', label: 'Questions and a worked example', icon: ArrowUpRight },
];

/** Each line of the challenge brief, what Continuum does about it, and where to see it. */
const asks = computed(() => [
    {
        ask: 'Harmonize MODIS and VIIRS so that the sensor of origin no longer matters.',
        does: t(
            'Both sensors on one 1 km grid; each sensor’s satellites merged; a monthly MODIS ÷ VIIRS ratio fitted on the days both flew, with a bootstrap interval.',
        ),
        proof: fit.value?.validation
            ? t('−:p error vs a naive merge on :n held-out days', {
                  p: percent(fit.value.validation.improvement),
                  n: number(fit.value.validation.test_days),
              })
            : '',
        links: [
            ['/method', t('Method')],
            ['/how-it-works', t('How it works')],
        ],
    },
    {
        ask: 'A web application where a user selects a region and a specific date range.',
        does: t('Five areas, :n ready-made places, and any box drawn on the map; any date range compared with the same dates in every other year.', {
            n: number(props.counts.places),
        }),
        proof: '',
        links: [
            ['/explore', t('Explore')],
            [`${area.value}#compare`, t('Compare dates')],
        ],
    },
    {
        ask: 'A calendar of burning activity from the early 2000s to the present, built on the harmonized dataset.',
        does: t('A month × year calendar from :range on the MODIS or the VIIRS scale, with the two sensors and the harmonized record side by side.', {
            range: dateRange(props.status.first_day, props.status.last_day),
        }),
        proof: t(':n years, :d NASA FIRMS detections', { n: String(years.value), d: number(props.status.detections) }),
        links: [[area.value, t('Dominican Republic calendar')]],
    },
    {
        ask: 'Which periods of the year see more fires, and which years had more burning activity.',
        does: t(
            'The fire season of each area (start, peak, end, share of the year, in season now or not) and the year-by-year totals in the calendar.',
        ),
        proof: '',
        links: [
            [`${area.value}#season`, t('Fire season')],
            ['/areas', t('All areas')],
        ],
    },
    {
        ask: 'How a current fire compares with previous ones; the current month against the historical median.',
        does: t('Every week scored against the same week in every other year (median and MAD), ranked among years, and flagged when unusual.'),
        proof: props.focus?.top
            ? t('Week of :date in :area: ×:t normal', {
                  date: date(props.focus.top.week_start),
                  area: props.focus.name,
                  t: number(props.focus.top.times, 1),
              })
            : '',
        links: [
            ['/alerts', t('Unusual periods')],
            [topWeek.value, t('Most unusual week')],
        ],
    },
    {
        ask: 'Download analytics and visualizations; a downloadable CSV of the hotspots detected during a search.',
        does: t(
            'Every map downloads as a PNG with its caption and as a CSV of the 1 km cells with fire; every fire calendar downloads as a PNG; monthly CSVs per area; and a public JSON API for every number.',
        ),
        proof: '',
        links: [['/api-docs', t('API and downloads')]],
    },
    {
        ask: 'Accessible color ramps to compare months.',
        does: t(
            'One single-hue sequential ramp (light to dark orange) in both light and dark mode, and the number itself on hover, so the colour never carries the meaning alone.',
        ),
        proof: '',
        links: [[area.value, t('Calendar')]],
    },
    {
        ask: 'A map where users can draw the area of interest.',
        does: t('Draw any box inside the areas and get its fit, season, calendar and unusual weeks in seconds; sign in to save boxes.'),
        proof: '',
        links: [['/explore', t('Explore')]],
    },
    {
        ask: 'Early warning and emergency responders, scientists, land managers and firefighters as the audience.',
        does: t(
            'A plain-language bulletin per area (English and Spanish) written by AI from the computed facts and checked number by number; the method, the limits and the uncertainty on one page for scientists.',
        ),
        proof: '',
        links: [
            [area.value, t('Bulletin')],
            ['/method', t('Method')],
        ],
    },
    {
        ask: 'Historical comparisons, including anomalies against multi-year averages.',
        does: t(
            'Anomaly detection by robust statistics: each week against the median and MAD of the same week in every other year, each date range ranked among 26 years. AI then writes the situation reading and the bulletins from those numbers, never choosing them.',
        ),
        proof: '',
        links: [
            ['/alerts', t('Unusual periods')],
            ['/alerts#reading', t('Situation reading')],
        ],
    },
    {
        ask: 'Enable early warning for emergency responders.',
        does: t(
            'Fire alerts by email for any drawn zone: every day the last complete week is scored against the same week in every other year, one email per flagged week, a test alert on demand, and SMS coming soon. The decision rule and the scheduled jobs are written on the page.',
        ),
        proof: '',
        links: [
            ['/alerts/subscribe', t('Fire alerts')],
            ['/alerts/subscribe#how', t('How an alert is decided')],
        ],
    },
    {
        ask: 'Anticipate critical periods.',
        does: t(
            'The fire season of each area, the weekly anomaly alerts, and a one-week forecast per area: ridge regression on this harmonized record and NASA POWER weather, validated year by year since 2013 against climatology and persistence, shown with its skill on every area page.',
        ),
        proof: '',
        links: [
            [`${area.value}#outlook`, t('Next week outlook')],
            ['/method#forecast', t('Forecasting method and results')],
        ],
    },

    {
        ask: 'MODIS is approaching the end of its operational life.',
        does: t('A retirement test: switch MODIS off in a chosen year, rebuild the later record from VIIRS alone and score it against the real one.'),
        proof: '',
        links: [[`${area.value}#retire`, t('Retirement test')]],
    },
]);

const criteria = computed(() => [
    {
        name: 'Impact',
        asks: 'Addresses a significant problem; benefits a large audience.',
        does: t(
            'One priority job for one named user: the forest-fire unit of the Ministry of Environment opens the Dominican Republic page on Monday and sets the readiness of the week from the outlook, the bulletin and 26 years of the same week; provincial COEs get an email when their territory turns unusual. Built first for the Dominican Republic and Haiti, proven on Cuba, the Gran Chaco and the Cerrado, free for any agency.',
        ),
        links: [
            ['/resume', t('Overview')],
            ['/areas', t('Areas')],
        ],
    },
    {
        name: 'Creativity',
        asks: 'Original, or a meaningful improvement on existing ideas.',
        does: t(
            'Cell-days instead of pixel counts, a monthly ratio with a bootstrap interval, a held-out test against the naive merge, weeks scored against the same week in other years, a MODIS retirement simulation, and AI that explains without deciding: anomalies are detected by robust statistics, then a bulletin per area and a situation reading across areas are written from those numbers only, each figure checked.',
        ),
        links: [
            ['/ai', t('AI analysis')],
            ['/how-it-works', t('How it works')],
        ],
    },
    {
        name: 'Validity',
        asks: 'Scientifically sound, technically feasible, usable.',
        does: fit.value?.validation
            ? t(
                  'Every number has its test: −:p daily error vs a naive merge on :n held-out days (:r) for the Dominican Republic; estimated values are marked with their interval; 46 automated tests; live and public, no login.',
                  { p: percent(fit.value.validation.improvement), n: number(fit.value.validation.test_days), r: r(fit.value.validation.correlation) },
              )
            : '',
        links: [
            ['/method', t('Method')],
            ['/data', t('Ingestion')],
        ],
    },
    {
        name: 'Relevance',
        asks: 'Directly addresses the challenge; NASA data is the core.',
        does: t(
            'Three NASA sources, each doing real work: FIRMS (MODIS C6.1 and VIIRS 375 m) for every detection, refreshed hourly; POWER for the daily weather that drives the next-week forecast; GIBS for the imagery under every map. The table above walks through each line of the challenge brief.',
        ),
        links: [['/data', t('Ingestion')]],
    },
    {
        name: 'Presentation',
        asks: 'Clear challenge, solution and significance; a well-structured demo.',
        does: t(
            'A 30-second demo, a 7-slide deck, a 4:16 explanation in Spanish with English subtitles, and a site that answers its own questions on the home page.',
        ),
        links: [['/method', t('Videos and slides')]],
    },
]);

const rules = computed(() => [
    t('Demo within the limits: a 30-second video in English and a 7-slide deck.'),
    t('Everything on the NASA project page is in English; the Spanish video carries English subtitles.'),
    t('All code was written during the event (14–15 November 2026), starting from the Laravel starter kit; the git history shows it.'),
    t(
        'The raw NASA FIRMS detections for the areas were downloaded before the event, which the Santiago organizers allow, and this is stated here, on the Ingestion page and in the README.',
    ),
    t(
        'AI use is declared: Claude Code for coding help, ElevenLabs and Microsoft Edge voices for the narration, and Gemma 4 inside the app for the bulletin, each with its purpose and a link.',
    ),
    t('No NASA logos or mission identifiers are used.'),
]);
</script>

<template>
    <Head :title="t('For judges')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('For judges') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Team Continuum, Santiago (Dominican Republic) local event, challenge “Harmonization of MODIS and VIIRS Hot Spots”. What the brief and the judging criteria ask, what this app does about each, and where to check it.',
                )
            }}
        </p>

        <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <li v-for="res in resources" :key="res.href">
                <a :href="res.href" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 underline-offset-4 hover:underline">
                    <component :is="res.icon" class="size-4 text-orange-600" /> {{ t(res.label) }}
                </a>
            </li>
        </ul>

        <section class="mt-12">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('What the challenge asks') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('Each line is taken from the challenge brief on spaceappschallenge.org.') }}</p>
            <ol class="mt-6 divide-y">
                <li v-for="(a, i) in asks" :key="i" class="grid gap-x-8 gap-y-2 py-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_12rem]">
                    <div class="flex gap-3">
                        <Check class="mt-0.5 size-4 shrink-0 text-orange-600" />
                        <p class="font-medium">{{ t(a.ask) }}</p>
                    </div>
                    <div class="pl-7 md:pl-0">
                        <p class="text-sm text-muted-foreground">{{ a.does }}</p>
                        <p v-if="a.proof" class="mt-1 text-sm font-medium tabular-nums">{{ a.proof }}</p>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 pl-7 text-sm md:flex-col md:pl-0">
                        <Link v-for="[href, label] in a.links" :key="href" :href="href" class="underline-offset-4 hover:underline"
                            >{{ label }} →</Link
                        >
                    </div>
                </li>
            </ol>
        </section>

        <section class="mt-12">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('Impact that can be measured: the decisions this information enables') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                {{
                    t(
                        'The challenge is met; what remains is impact. Each decision below names who takes it, with what number, and how its effect will be counted.',
                    )
                }}
            </p>
            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 font-normal">{{ t('Decision') }}</th>
                            <th class="font-normal">{{ t('Who') }}</th>
                            <th class="font-normal">{{ t('With what') }}</th>
                            <th class="font-normal">{{ t('Measured by') }}</th>
                        </tr>
                    </thead>
                    <tbody class="align-top">
                        <tr class="border-t">
                            <td class="py-3 pr-4 font-medium">{{ t('Set the readiness level of the week') }}</td>
                            <td class="pr-4">{{ t('Forest-fire unit, Ministry of Environment') }}</td>
                            <td class="pr-4 text-muted-foreground">
                                {{ t('Next-week outlook (×N with range and skill) and the bulletin, every Monday') }}
                            </td>
                            <td class="text-muted-foreground">
                                {{
                                    t(
                                        'Lead time: 7 days before the week; outlook skill −19% error vs climatology for the Dominican Republic, tracked every year',
                                    )
                                }}
                            </td>
                        </tr>
                        <tr class="border-t">
                            <td class="py-3 pr-4 font-medium">{{ t('Pre-position brigades where the week turned unusual') }}</td>
                            <td class="pr-4">{{ t('Provincial COE, municipal brigades') }}</td>
                            <td class="pr-4 text-muted-foreground">
                                {{ t('Email alert within a day of NASA data, with the map of the burning cells') }}
                            </td>
                            <td class="tabular-nums text-muted-foreground">
                                {{
                                    t(
                                        ':n unusual weeks flagged in the Dominican Republic since 2000 (:e extreme), :r in the last five years; alerts sent and response time, counted from the first subscription (:s today)',
                                        {
                                            n: number(Object.values(impact.focus_weeks).reduce((a, b) => a + b, 0)),
                                            e: number(impact.focus_weeks.extreme ?? 0),
                                            r: number(impact.focus_weeks_5y),
                                            s: number(impact.subscriptions),
                                        },
                                    )
                                }}
                            </td>
                        </tr>
                        <tr class="border-t">
                            <td class="py-3 pr-4 font-medium">{{ t('Time burn restrictions and public warnings to the season') }}</td>
                            <td class="pr-4">{{ t('Ministry of Environment, municipalities') }}</td>
                            <td class="pr-4 text-muted-foreground">{{ t('The fire season of each area and "in season now"') }}</td>
                            <td class="text-muted-foreground">
                                {{
                                    t(
                                        'Share of the year’s fire inside the declared window (Dominican Republic: March–April, 44%), re-measured each year',
                                    )
                                }}
                            </td>
                        </tr>
                        <tr class="border-t">
                            <td class="py-3 pr-4 font-medium">{{ t('Justify budgets and reports with a baseline') }}</td>
                            <td class="pr-4">{{ t('Planners, researchers, press') }}</td>
                            <td class="pr-4 text-muted-foreground">{{ t('Compare any dates with 26 years; CSV and API') }}</td>
                            <td class="tabular-nums text-muted-foreground">
                                {{
                                    t(':k km² covered in five areas, :w unusual weeks on record, zero cost: free NASA data, one small server', {
                                        k: number(impact.km2),
                                        w: number(impact.all_weeks),
                                    })
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('The five judging criteria') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('From the NASA Space Apps judging guide: each scored 1 to 5 at every level.') }}</p>
            <div class="mt-6 grid gap-x-8 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="c in criteria" :key="c.name">
                    <div class="font-medium">{{ t(c.name) }}</div>
                    <div class="text-xs text-muted-foreground">{{ t(c.asks) }}</div>
                    <p class="mt-2 text-sm">{{ c.does }}</p>
                    <div class="mt-2 flex flex-wrap gap-x-4 text-sm">
                        <Link
                            v-for="[href, label] in c.links"
                            :key="href"
                            :href="href"
                            class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                            >{{ label }} →</Link
                        >
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('Rules we followed') }}</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li v-for="rule in rules" :key="rule" class="flex gap-3">
                    <Check class="mt-0.5 size-4 shrink-0 text-orange-600" /><span>{{ rule }}</span>
                </li>
            </ul>
            <p class="mt-4 text-xs text-muted-foreground">
                {{ t('Full text in the repository: README.md, docs/SUBMISSION.md and CREDITS.md.') }}
            </p>
        </section>
    </SiteLayout>
</template>
