<script setup lang="ts">
import type { Fit, Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ status: Status; evidence: { slug: string; name: string; fit: Fit | null }[] }>();

const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
const modis = computed(() => props.status.instruments.find((i) => i.instrument === 'modis'));
const weakest = computed(
    () => [...props.evidence].filter((e) => e.fit?.validation).sort((a, b) => a.fit!.validation!.correlation - b.fit!.validation!.correlation)[0],
);
</script>

<template>
    <Head :title="t('Method')" />
    <SiteLayout>
        <article class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight">{{ t('How Continuum harmonizes MODIS and VIIRS') }}</h1>
            <p class="mt-3 text-muted-foreground">
                {{
                    t(
                        'The fire record is split across two sensors that disagree by design. Continuum removes the part of the disagreement that is geometry, models the part that is physics on the years both sensors flew, and reports how well that holds on days the fit never saw.',
                    )
                }}
            </p>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">1 · {{ t('Three problems, not one') }}</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="font-medium">{{ t('Pixel size') }}</dt>
                        <dd class="text-muted-foreground">
                            {{
                                t(
                                    'A MODIS pixel is about 1 km; a VIIRS pixel is 375 m, so one fire front is one MODIS pixel but several VIIRS pixels. That is geometry, and it can be removed exactly.',
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ t('Number of satellites') }}</dt>
                        <dd class="text-muted-foreground">
                            {{
                                t(
                                    'MODIS flies on Terra and Aqua; VIIRS on Suomi NPP (2012), NOAA-20 (2018) and NOAA-21 (2023). Raw counts grow with every launch even if burning does not.',
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ t('Sensitivity') }}</dt>
                        <dd class="text-muted-foreground">
                            {{
                                t(
                                    'A 375 m pixel detects fires too small or cool for a 1 km pixel. That is physics: it cannot be removed, only measured and modelled.',
                                )
                            }}
                        </dd>
                    </div>
                </dl>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ t('Joined as they are, the two records show a fire boom in 2012 that never happened.') }}
                </p>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">2 · {{ t('One grid and the cell-day') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{
                        t(
                            'Every NASA FIRMS detection goes into a 0.01° grid cell (about 1 km at these latitudes). The unit of fire is the active cell-day: one cell with at least one detection from a sensor on one UTC day, with all of that sensor’s satellites merged. A cell seen by NOAA-20 and NOAA-21 on the same day counts once.',
                        )
                    }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        t('Live: VIIRS averages :v pixels per cell-day, MODIS :m. After gridding, what is left between the two is sensitivity.', {
                            v: number(viirs?.pixels_per_cell, 1),
                            m: number(modis?.pixels_per_cell, 1),
                        })
                    }}
                </p>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">3 · {{ t('One ratio, fitted where both sensors flew') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{
                        t(
                            'Since 20 January 2012 both sensors have observed the same fires. On the days both delivered data, Continuum fits r = Σ MODIS cell-days ÷ Σ VIIRS cell-days for the whole year and for each calendar month with at least 20 days. A 200-sample bootstrap over days gives the 95% interval shown as the shaded band.',
                        )
                    }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        t(
                            'On the MODIS scale, MODIS is used whenever it flew and VIIRS × r only where it did not. On the VIIRS scale, VIIRS is used from 2012 and MODIS ÷ r before. Every converted value is marked as estimated, in the charts, the CSV and the API.',
                        )
                    }}
                </p>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">4 · {{ t('Validation you can check') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{
                        t(
                            'Before a ratio is kept, it is fitted on three of every four overlap days and scored on the fourth (every fourth day, so every season is in both sets), against a naive merge that just switches to VIIRS. The kept ratio is then refitted on all overlap days.',
                        )
                    }}
                </p>
            </section>
        </article>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full max-w-5xl text-sm">
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
                        <td class="text-right font-medium">{{ e.fit?.validation ? `−${percent(e.fit.validation.improvement)}` : '—' }}</td>
                        <td class="text-right">{{ r(e.fit?.validation?.correlation) }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-2 text-xs text-muted-foreground">
                {{ t('Live numbers. Daily error is the mean absolute error in cell-days per day on held-out days.') }}
            </p>
        </div>

        <article class="max-w-3xl">
            <section class="mt-8">
                <h3 class="font-medium">{{ t('Where it works worst') }}</h3>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                    <li v-if="weakest">
                        {{
                            t(
                                ':name has the weakest daily fit (r :r): its fires are small and scattered, so one extra pixel is a large share of a day. Monthly totals are far steadier than single days.',
                                { name: weakest.name, r: number(weakest.fit?.validation?.correlation, 2) },
                            )
                        }}
                    </li>
                    <li>
                        {{
                            t('A box with little fire leaves few overlap days to fit; Explore refuses boxes with too little fire rather than guess.')
                        }}
                    </li>
                    <li>
                        {{
                            t(
                                'The ratio drifts over time. The MODIS retirement test shows totals two to three years ahead off by up to about 25% in some areas, while the monthly shape holds (r 0.93–0.996).',
                            )
                        }}
                    </li>
                    <li>{{ t('Cloud and thick smoke hide fires from both sensors; a cloudy day looks like a day without fire.') }}</li>
                </ul>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">5 · {{ t('Fire season, unusual weeks and date comparisons') }}</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="font-medium">{{ t('Fire season') }}</dt>
                        <dd class="text-muted-foreground">
                            {{
                                t(
                                    'The median of each calendar month over complete years, on the MODIS scale. The season is the run of months around the peak that holds at least half of the peak month’s fire.',
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ t('Unusual weeks') }}</dt>
                        <dd class="text-muted-foreground">
                            {{
                                t(
                                    'Each complete week (Monday to Sunday) against the same week of the year in at least five other years: median and median absolute deviation, so one extreme year cannot hide another. A week is flagged at 3 deviations or more, at least twice the median and at least 10 cell-days; 5 is high and 8 extreme.',
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ t('Compare any dates') }}</dt>
                        <dd class="text-muted-foreground">
                            {{
                                t(
                                    'Any range of up to a year against the same calendar dates in every year with data on every day of the range: the median, times normal and the rank.',
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">6 · {{ t('When MODIS retires') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{
                        t(
                            'NASA plans to stop Aqua MODIS around August–September 2026 and Terra MODIS around January 2027. Each area page simulates it: pick the year MODIS stops, fit only on earlier days, rebuild every later month from VIIRS alone and compare with the MODIS record that really exists.',
                        )
                    }}
                </p>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">7 · {{ t('Data and limits') }}</h2>
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
        </article>
    </SiteLayout>
</template>
