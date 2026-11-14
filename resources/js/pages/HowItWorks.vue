<script setup lang="ts">
import type { Fit, Status } from '@/components/fire/types';
import WeekMap from '@/components/fire/WeekMap.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { dateRange, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status: Status;
    evidence: { slug: string; name: string; fit: Fit | null }[];
    hispaniola: { west: number; south: number; east: number; north: number } | null;
}>();

const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
const modis = computed(() => props.status.instruments.find((i) => i.instrument === 'modis'));
const bbox = computed(() =>
    props.hispaniola ? [props.hispaniola.west, props.hispaniola.south, props.hispaniola.east, props.hispaniola.north] : [-74.9, 16.9, -65.4, 22.6],
);

const steps = computed(() => [
    [
        t('NASA FIRMS'),
        t(':n raw fire pixels: MODIS since November 2000, VIIRS since January 2012. New pixels every hour.', { n: number(props.status.detections) }),
    ],
    [
        t('One 1 km grid'),
        t('Pixels become :n cell-days. VIIRS averages :v pixels per cell, MODIS :m.', {
            n: number(props.status.cell_days),
            v: number(viirs.value?.pixels_per_cell, 1),
            m: number(modis.value?.pixels_per_cell, 1),
        }),
    ],
    [t('Area totals'), t('Each area counts its burning cells per day and sensor, with every satellite of a sensor merged.')],
    [t('Fit and test'), t('MODIS ÷ VIIRS per calendar month on the overlap days, fitted on 3 of every 4 days and tested on the 4th.')],
    [t('One record'), t('Each day on the MODIS or VIIRS scale; converted days are marked as estimated, with their interval.')],
    [t('Unusual weeks'), t('Every week against the same week in all other years.')],
    [t('Pages, maps and API'), t('Everything here is also open as JSON and CSV, with no login.')],
]);
</script>

<template>
    <Head :title="t('How it works')" />
    <SiteLayout>
        <section class="grid items-center gap-8 lg:grid-cols-[1fr_1.1fr]">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ t('Two satellites, one record you can check') }}</h1>
                <p class="mt-3 text-muted-foreground">
                    {{ t('The pipeline, the evidence and the machine behind Continuum. Every number on this page is read live from the system.') }}
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-4 text-sm">
                    <a href="#pipeline" class="rounded-md bg-foreground px-3 py-2 text-background">{{ t('See the pipeline') }}</a>
                    <Link href="/method" class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">{{
                        t('Read the method')
                    }}</Link>
                </div>
            </div>
            <WeekMap :bbox="bbox" :days="30" />
        </section>

        <dl class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('NASA FIRMS detections') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number(status.detections) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Years of record') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">26</dd>
                <dd class="text-xs text-muted-foreground">{{ dateRange(status.first_day, status.last_day) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Satellites, two sensors') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ status.satellites.length }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('VIIRS pixels per 1 km cell') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">×{{ number(viirs?.pixels_per_cell, 1) }}</dd>
            </div>
        </dl>

        <section id="pipeline" class="mt-14 scroll-mt-6">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('The pipeline') }}</h2>
            <ol class="mt-6 grid gap-x-10 gap-y-6 md:grid-cols-2">
                <li v-for="([title, body], i) in steps" :key="i" class="grid grid-cols-[2rem_1fr] gap-2">
                    <span class="tabular-nums text-muted-foreground">{{ String(i + 1).padStart(2, '0') }}</span>
                    <span>
                        <span class="block font-medium">{{ title }}</span>
                        <span class="text-sm text-muted-foreground">{{ body }}</span>
                    </span>
                </li>
            </ol>
        </section>

        <section class="mt-14">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('The evidence') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                {{
                    t(
                        'Each area fits its own MODIS ÷ VIIRS ratio on three of every four overlap days and is scored on the fourth, against a naive merge that just switches sensors.',
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

        <section class="mt-14">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('The machine') }}</h2>
            <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                {{
                    t(
                        'Code lives in github.com/elminson/continuum-spaceapps. A push to main triggers Spinnr, which updates one Linux server running Nginx, PHP and PostgreSQL. Every hour the scheduler pulls NASA FIRMS, re-processes the recent days and pre-computes the pages.',
                    )
                }}
            </p>
        </section>
    </SiteLayout>
</template>
