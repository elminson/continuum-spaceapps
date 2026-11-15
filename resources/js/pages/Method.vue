<script setup lang="ts">
import type { ForecastData } from '@/components/fire/ForecastCard.vue';
import FormulaInfo from '@/components/fire/FormulaInfo.vue';
import type { Fit, Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { locale, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { Code, FileText, Github, PlayCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    status: Status;
    evidence: { slug: string; name: string; fit: Fit | null }[];
    example: { area: string; modis: number; viirs: number; days: number } | null;
    forecasts: { slug: string; name: string; forecast: ForecastData | null }[];
}>();

const oneWeek = (f: ForecastData) => f.current?.validation ?? f.validation;
const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
const modis = computed(() => props.status.instruments.find((i) => i.instrument === 'modis'));
const weakest = computed(
    () => [...props.evidence].filter((e) => e.fit?.validation).sort((a, b) => a.fit!.validation!.correlation - b.fit!.validation!.correlation)[0],
);
const best = computed(() => {
    const v = props.evidence.map((e) => e.fit?.validation?.improvement).filter((x): x is number => typeof x === 'number');
    return v.length ? { min: Math.min(...v), max: Math.max(...v) } : null;
});

const toc = [
    ['media', 'Presentation & videos'],
    ['problems', 'Three problems, not one'],
    ['grid', 'One grid and the cell-day'],
    ['ratio', 'One ratio, fitted where both sensors flew'],
    ['validation', 'Validation you can check'],
    ['products', 'Fire season, unusual weeks and date comparisons'],
    ['retirement', 'When MODIS retires'],
    ['limits', 'Data and limits'],
    ['forecast', 'Next: forecasting'],
] as const;

const VIDEOS = {
    es: {
        youtube: 'https://youtu.be/D-HypfIguYk',
        src: '/videos/continuum-local-es.mp4',
        poster: '/videos/continuum-local-es.jpg',
        label: '4:16 explanation (Spanish, English subtitles)',
        audience: 'for the Santiago judges',
    },
    en: {
        youtube: 'https://youtu.be/ZkYbmGTxsQQ',
        src: '/videos/continuum-demo-en.mp4',
        poster: '/videos/continuum-demo-en.jpg',
        label: '30-second demo (English)',
        audience: 'for the NASA global judges',
    },
};
const main = computed(() => (locale() === 'es' ? VIDEOS.es : VIDEOS.en));
const other = computed(() => (locale() === 'es' ? VIDEOS.en : VIDEOS.es));

const current = ref<string>('media');
let observer: IntersectionObserver | null = null;
onMounted(() => {
    observer = new IntersectionObserver((entries) => entries.forEach((e) => e.isIntersecting && (current.value = e.target.id)), {
        rootMargin: '-20% 0px -70% 0px',
    });
    toc.forEach(([id]) => document.getElementById(id) && observer!.observe(document.getElementById(id)!));
});
onBeforeUnmount(() => observer?.disconnect());

const resources = computed(() => [
    { href: main.value.youtube, label: main.value.label, icon: PlayCircle },
    { href: '/slides/continuum-slides.pdf', label: '7-slide deck (PDF)', icon: FileText },
    { href: other.value.youtube, label: other.value.label, icon: PlayCircle },
    { href: 'https://github.com/elminson/continuum-spaceapps', label: 'Code on GitHub', icon: Github },
    { href: '/api-docs', label: 'Open API', icon: Code },
]);
</script>

<template>
    <Head :title="t('Method')" />
    <SiteLayout>
        <header class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight">{{ t('How Continuum harmonizes MODIS and VIIRS') }}</h1>
            <p class="mt-3 text-muted-foreground">
                {{
                    t(
                        'The fire record is split across two sensors that disagree by design. Continuum removes the part of the disagreement that is geometry, models the part that is physics on the years both sensors flew, and reports how well that holds on days the fit never saw.',
                    )
                }}
            </p>
        </header>

        <nav class="mt-6 flex flex-wrap gap-2 text-sm" :aria-label="t('Resources')">
            <a
                v-for="item in resources"
                :key="item.href"
                :href="item.href"
                :target="item.href.startsWith('http') || item.href.endsWith('.pdf') ? '_blank' : undefined"
                rel="noopener"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 hover:bg-muted"
            >
                <component :is="item.icon" class="size-4 text-orange-600" />
                {{ t(item.label) }}
            </a>
        </nav>

        <div class="mt-10 grid gap-10 lg:grid-cols-[13rem_1fr]">
            <aside class="hidden lg:block">
                <ol class="sticky top-6 space-y-1 border-l text-sm">
                    <li v-for="([id, label], i) in toc" :key="id">
                        <a
                            :href="`#${id}`"
                            class="-ml-px block border-l-2 py-1 pl-3 transition-colors"
                            :class="
                                current === id
                                    ? 'border-orange-600 font-medium text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span class="tabular-nums">{{ i === 0 ? '▶' : i }}</span> · {{ t(label) }}
                        </a>
                    </li>
                </ol>
            </aside>

            <div class="min-w-0 max-w-3xl space-y-16">
                <section id="media" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">{{ t('Presentation & videos') }}</h2>
                    <figure class="mt-4">
                        <video :key="main.src" controls preload="metadata" :poster="main.poster" class="aspect-video w-full rounded-md bg-black">
                            <source :src="main.src" type="video/mp4" />
                        </video>
                        <figcaption class="mt-2 text-sm">
                            {{ t(main.label) }} <span class="text-muted-foreground"> · {{ t(main.audience) }}</span>
                        </figcaption>
                    </figure>
                    <a
                        :href="main.youtube"
                        target="_blank"
                        rel="noopener"
                        class="mr-4 mt-3 inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <PlayCircle class="size-4" /> {{ t('Watch on YouTube') }}
                    </a>
                    <a
                        :href="other.youtube"
                        target="_blank"
                        class="mt-3 inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <PlayCircle class="size-4" /> {{ t('Also available: :label', { label: t(other.label) }) }}
                    </a>
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{ t('Narration is an AI-generated voice; every image is a real screen of this app.') }}
                        <a href="/slides/continuum-slides.pdf" target="_blank" class="text-foreground underline underline-offset-4">{{
                            t('Open the 7-slide deck')
                        }}</a>
                    </p>
                </section>

                <section id="problems" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">1 · {{ t('Three problems, not one') }}</h2>
                    <div class="mt-4 grid gap-6 sm:grid-cols-3">
                        <div>
                            <div class="text-2xl font-semibold tabular-nums">1 km <span class="text-muted-foreground">vs</span> 375 m</div>
                            <div class="mt-1 font-medium">{{ t('Pixel size') }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    t(
                                        'A MODIS pixel is about 1 km; a VIIRS pixel is 375 m, so one fire front is one MODIS pixel but several VIIRS pixels. That is geometry, and it can be removed exactly.',
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold tabular-nums">2 <span class="text-muted-foreground">vs</span> 3</div>
                            <div class="mt-1 font-medium">{{ t('Number of satellites') }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    t(
                                        'MODIS flies on Terra and Aqua; VIIRS on Suomi NPP (2012), NOAA-20 (2018) and NOAA-21 (2023). Raw counts grow with every launch even if burning does not.',
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold tabular-nums text-orange-700 dark:text-orange-400">
                                ×{{ number(1 / (evidence[0]?.fit?.ratio ?? 0.129), 1) }}
                            </div>
                            <div class="text-xs text-muted-foreground">{{ t('VIIRS cell-days per MODIS cell-day in the Dominican Republic') }}</div>
                            <div class="mt-1 font-medium">{{ t('Sensitivity') }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    t(
                                        'A 375 m pixel detects fires too small or cool for a 1 km pixel. That is physics: it cannot be removed, only measured and modelled.',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-muted-foreground">
                        {{ t('Joined as they are, the two records show a fire boom in 2012 that never happened.') }}
                    </p>
                </section>

                <section id="grid" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">2 · {{ t('One grid and the cell-day') }}</h2>
                    <p class="mt-3 text-muted-foreground">
                        {{
                            t(
                                'Every NASA FIRMS detection goes into a 0.01° grid cell (about 1 km at these latitudes). The unit of fire is the active cell-day: one cell with at least one detection from a sensor on one UTC day, with all of that sensor’s satellites merged. A cell seen by NOAA-20 and NOAA-21 on the same day counts once.',
                            )
                        }}
                    </p>
                    <dl class="mt-4 grid grid-cols-2 gap-6 sm:grid-cols-4">
                        <div>
                            <dt class="text-sm text-muted-foreground">{{ t('NASA FIRMS detections') }}</dt>
                            <dd class="text-xl font-semibold tabular-nums">{{ number(status.detections) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">{{ t('1 km cell-days') }}</dt>
                            <dd class="text-xl font-semibold tabular-nums">{{ number(status.cell_days) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">{{ t('VIIRS pixels per cell') }}</dt>
                            <dd class="text-xl font-semibold tabular-nums">{{ number(viirs?.pixels_per_cell, 1) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">{{ t('MODIS pixels per cell') }}</dt>
                            <dd class="text-xl font-semibold tabular-nums">{{ number(modis?.pixels_per_cell, 1) }}</dd>
                        </div>
                    </dl>
                </section>

                <section id="ratio" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">3 · {{ t('One ratio, fitted where both sensors flew') }}</h2>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-md bg-muted px-4 py-3 font-mono text-sm">
                        <span>r = Σ MODIS cell-days ÷ Σ VIIRS cell-days</span>
                        <FormulaInfo :example="example" />
                    </div>
                    <p class="mt-3 text-muted-foreground">
                        {{
                            t(
                                'Since 20 January 2012 both sensors have observed the same fires. On the days both delivered data, Continuum fits r = Σ MODIS cell-days ÷ Σ VIIRS cell-days for the whole year and for each calendar month with at least 20 days. A 200-sample bootstrap over days gives the 95% interval shown as the shaded band.',
                            )
                        }}
                    </p>
                    <p class="mt-2 text-muted-foreground">
                        {{
                            t(
                                'On the MODIS scale, MODIS is used whenever it flew and VIIRS × r only where it did not. On the VIIRS scale, VIIRS is used from 2012 and MODIS ÷ r before. Every converted value is marked as estimated, in the charts, the CSV and the API.',
                            )
                        }}
                    </p>
                </section>

                <section id="validation" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">4 · {{ t('Validation you can check') }}</h2>
                    <p v-if="best" class="mt-3 text-3xl font-semibold tabular-nums text-orange-700 dark:text-orange-400">
                        −{{ percent(best.min) }} … −{{ percent(best.max) }}
                        <span class="text-base font-normal text-muted-foreground">{{ t('error vs naive merge, on held-out days') }}</span>
                    </p>
                    <p class="mt-3 text-muted-foreground">
                        {{
                            t(
                                'Before a ratio is kept, it is fitted on three of every four overlap days and scored on the fourth (every fourth day, so every season is in both sets), against a naive merge that just switches to VIIRS. The kept ratio is then refitted on all overlap days.',
                            )
                        }}
                    </p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-muted-foreground">
                                <tr>
                                    <th class="py-2 font-normal">{{ t('Area') }}</th>
                                    <th class="text-right font-normal">MODIS ÷ VIIRS</th>
                                    <th class="text-right font-normal">{{ t('Held-out days') }}</th>
                                    <th class="text-right font-normal">{{ t('Daily error') }}</th>
                                    <th class="text-right font-normal">{{ t('Naive merge') }}</th>
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
                                    <td class="text-right">{{ number(e.fit?.validation?.mae, 1) }}</td>
                                    <td class="text-right">{{ number(e.fit?.validation?.naive_mae, 1) }}</td>
                                    <td class="text-right font-medium">
                                        {{ e.fit?.validation ? `−${percent(e.fit.validation.improvement)}` : '—' }}
                                    </td>
                                    <td class="text-right">{{ r(e.fit?.validation?.correlation) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ t('Live numbers. Daily error is the mean absolute error in cell-days per day on held-out days.') }}
                    </p>

                    <h3 class="mt-8 font-medium">{{ t('Where it works worst') }}</h3>
                    <ul class="mt-2 space-y-2 text-sm text-muted-foreground">
                        <li v-if="weakest" class="border-l-2 border-orange-600/60 pl-3">
                            {{
                                t(
                                    ':name has the weakest daily fit (r :r): its fires are small and scattered, so one extra pixel is a large share of a day. Monthly totals are far steadier than single days.',
                                    {
                                        name: weakest.name,
                                        r: number(weakest.fit?.validation?.correlation, 2),
                                    },
                                )
                            }}
                        </li>
                        <li class="border-l-2 border-orange-600/60 pl-3">
                            {{
                                t(
                                    'A box with little fire leaves few overlap days to fit; Explore refuses boxes with too little fire rather than guess.',
                                )
                            }}
                        </li>
                        <li class="border-l-2 border-orange-600/60 pl-3">
                            {{
                                t(
                                    'The ratio drifts over time. The MODIS retirement test shows totals two to three years ahead off by up to about 25% in some areas, while the monthly shape holds (r 0.93–0.996).',
                                )
                            }}
                        </li>
                        <li class="border-l-2 border-orange-600/60 pl-3">
                            {{ t('Cloud and thick smoke hide fires from both sensors; a cloudy day looks like a day without fire.') }}
                        </li>
                    </ul>
                </section>

                <section id="products" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">5 · {{ t('Fire season, unusual weeks and date comparisons') }}</h2>
                    <dl class="mt-4 grid gap-6 sm:grid-cols-3">
                        <div>
                            <dt class="font-medium">{{ t('Fire season') }}</dt>
                            <dd class="mt-1 text-sm text-muted-foreground">
                                {{
                                    t(
                                        'The median of each calendar month over complete years, on the MODIS scale. The season is the run of months around the peak that holds at least half of the peak month’s fire.',
                                    )
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ t('Unusual weeks') }}</dt>
                            <dd class="mt-1 text-sm text-muted-foreground">
                                {{
                                    t(
                                        'Each complete week (Monday to Sunday) against the same week of the year in at least five other years: median and median absolute deviation, so one extreme year cannot hide another. A week is flagged at 3 deviations or more, at least twice the median and at least 10 cell-days; 5 is high and 8 extreme.',
                                    )
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ t('Compare any dates') }}</dt>
                            <dd class="mt-1 text-sm text-muted-foreground">
                                {{
                                    t(
                                        'Any range of up to a year against the same calendar dates in every year with data on every day of the range: the median, times normal and the rank.',
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section id="retirement" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">6 · {{ t('When MODIS retires') }}</h2>
                    <p class="mt-3 text-muted-foreground">
                        {{
                            t(
                                'NASA plans to stop Aqua MODIS around August–September 2026 and Terra MODIS around January 2027. Each area page simulates it: pick the year MODIS stops, fit only on earlier days, rebuild every later month from VIIRS alone and compare with the MODIS record that really exists.',
                            )
                        }}
                    </p>
                    <Link href="/areas/hispaniola" class="mt-3 inline-block text-sm underline underline-offset-4">{{
                        t('Try it on Hispaniola →')
                    }}</Link>
                </section>

                <section id="limits" class="scroll-mt-6">
                    <h2 class="text-xl font-semibold tracking-tight">7 · {{ t('Data and limits') }}</h2>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        <li>{{ t('Active-fire data is positive-only: it records fires that were seen, not where a sensor looked.') }}</li>
                        <li>
                            {{
                                t(
                                    'A day counts as observed for a sensor when it delivered any fire detection in the processed areas that day; there is no per-cell observation mask yet.',
                                )
                            }}
                        </li>
                        <li>{{ t('Near-real-time detections from the hourly feeds are kept as they arrive.') }}</li>
                        <li>{{ t('Rectangular areas only.') }}</li>
                        <li>{{ t('Terra passes at 10:30, the others near 13:30: Terra samples a different time of day.') }}</li>
                    </ul>
                    <p class="mt-4 text-sm text-muted-foreground">
                        {{
                            t(
                                'Data: NASA FIRMS active fire products, MODIS Collection 6.1 (Giglio et al., 2016) and VIIRS 375 m (Schroeder et al., 2014); NASA GIBS imagery. Open JSON and CSV for everything on this site:',
                            )
                        }}
                        <Link href="/api-docs" class="underline underline-offset-4">API</Link>.
                    </p>
                </section>

                <section
                    id="forecast"
                    class="scroll-mt-6 rounded-md border border-orange-300/60 bg-orange-50/60 p-5 dark:border-orange-900 dark:bg-orange-950/20"
                >
                    <p class="text-sm font-medium text-orange-700 dark:text-orange-400">{{ t('Built during the event') }}</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">8 · {{ t('Forecasting the next week, with NASA data only') }}</h2>
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{
                            t(
                                'The challenge asks to anticipate critical periods. Fire counts alone cannot do it, because they do not contain the reason for next week’s fire; the drivers do, and NASA publishes them for free. The harmonized record built here is the missing piece: a consistent weekly target since 2000 to train and test against.',
                            )
                        }}
                    </p>
                    <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <div class="font-medium">{{ t('Target') }}</div>
                            <p class="text-muted-foreground">
                                {{ t('Burning 1 km cell-days per area and week, on the MODIS scale, from this record (2000 → today).') }}
                            </p>
                        </div>
                        <div>
                            <div class="font-medium">{{ t('Drivers (NASA, free)') }}</div>
                            <p class="text-muted-foreground">
                                {{
                                    t(
                                        'Today: NASA POWER daily weather at each area’s centre (temperature, humidity, wind, rain), 7- and 28-day windows. Next: GPM IMERG rainfall, SMAP soil moisture and MODIS/VIIRS vegetation indices for fuel amount and dryness.',
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <div class="font-medium">{{ t('Model') }}</div>
                            <p class="text-muted-foreground">
                                {{
                                    t(
                                        'Per area, ridge regression on 14 features: the last 1–4 weeks of fire, the same-week median of other years, the weather windows and the time of year. Gradient boosting is the next step, to be adopted only if it beats this model on the same test.',
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <div class="font-medium">{{ t('Validation, before anything is shown') }}</div>
                            <p class="text-muted-foreground">
                                {{
                                    t(
                                        'Rolling origin: for each year 2013 → 2026 the model is trained on the years before and tested on that year, against two baselines, the same-week climatology and persistence. Error and correlation are published next to every forecast, like the −89% test.',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div v-if="forecasts.some((f) => f.forecast)" class="mt-5 overflow-x-auto">
                        <div class="text-sm font-medium">{{ t('Results so far: one week ahead, rolling validation, one fold per year') }}</div>
                        <table class="mt-2 w-full text-sm">
                            <thead class="text-left text-muted-foreground">
                                <tr>
                                    <th class="py-2 font-normal">{{ t('Area') }}</th>
                                    <th class="text-right font-normal">{{ t('Next week') }}</th>
                                    <th class="text-right font-normal">{{ t('Error vs climatology') }}</th>
                                    <th class="text-right font-normal">{{ t('Error vs persistence') }}</th>
                                    <th class="text-right font-normal">{{ t('Correlation') }}</th>
                                    <th class="text-right font-normal">{{ t('Weeks tested') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tabular-nums">
                                <tr v-for="f in forecasts" :key="f.slug" class="border-t">
                                    <td class="py-2">
                                        <Link :href="`/areas/${f.slug}#outlook`" class="hover:underline">{{ f.name }}</Link>
                                    </td>
                                    <template v-if="f.forecast">
                                        <td class="text-right">
                                            {{
                                                f.forecast.times_normal !== null
                                                    ? `×${number(f.forecast.times_normal, 1)}`
                                                    : number(f.forecast.expected)
                                            }}
                                        </td>
                                        <td class="text-right font-medium">
                                            {{ oneWeek(f.forecast).skill_vs_climatology >= 0 ? '−' : '+'
                                            }}{{ percent(Math.abs(oneWeek(f.forecast).skill_vs_climatology)) }}
                                        </td>
                                        <td class="text-right">
                                            {{ oneWeek(f.forecast).skill_vs_persistence >= 0 ? '−' : '+'
                                            }}{{ percent(Math.abs(oneWeek(f.forecast).skill_vs_persistence)) }}
                                        </td>
                                        <td class="text-right">{{ r(oneWeek(f.forecast).correlation) }}</td>
                                        <td class="text-right">{{ number(oneWeek(f.forecast).weeks) }}</td>
                                    </template>
                                    <td v-else colspan="5" class="text-right text-muted-foreground">{{ t('not yet computed') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-sm">
                        {{
                            t(
                                'On each area page: “Next week outlook: ×N of normal (range)”, with its skill score; the range comes from the validation residuals. Statistics and the model decide; AI explains, as everywhere else on this site.',
                            )
                        }}
                    </p>
                </section>
            </div>
        </div>
    </SiteLayout>
</template>
