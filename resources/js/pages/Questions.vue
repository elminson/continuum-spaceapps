<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';

type Example = {
    area: string;
    day: string;
    cell: { lat: number; lon: number };
    detections: { instrument: string; satellite: string; time: string; latitude: number; longitude: number; frp: number }[];
    cell_days: { instrument: string; detections: number; frp: number }[];
    totals: Record<string, { cell_days: number; detections: number }>;
    ratio_month: { ratio: number; low: number; high: number; days: number } | null;
    ratio_year: number | null;
    harmonized: { modis: number | null; viirs: number | null; value: number | null; estimated: boolean } | null;
    overlap_index: number | null;
    overlap_days: number;
    held_out: boolean;
    validation: { train_days: number; test_days: number; mae: number; naive_mae: number; improvement: number; correlation: number } | null;
};

defineProps<{ example: Example | null; focusSlug: string }>();

const questions: { q: string; a: string; links: [string, string][] }[] = [
    {
        q: 'How do you get 89% less error?',
        a: 'Three steps and a test. (1) Every NASA FIRMS pixel goes into a 0.01° cell (about 1 km), so a fire front counts once even when VIIRS reports it with several 375 m pixels. (2) All satellites of each sensor are merged before counting. (3) The sensitivity gap that remains is measured as a MODIS ÷ VIIRS ratio per calendar month, fitted on the days both sensors flew, and used to convert one scale into the other. The test: the ratio is fitted on three of every four overlap days and scored on the fourth, against a naive merge. The 89% is how much smaller the daily error is on those held-out days, not an accuracy.',
        links: [
            ['/method#validation', 'Method'],
            ['/how-it-works', 'How it works'],
        ],
    },
    {
        q: 'What is a cell-day?',
        a: 'One square kilometre where some satellite saw fire on one day. It is the unit of the whole site because it is the only one both sensors can agree on: MODIS puts about 1.1 pixels in a burning cell, VIIRS 2.4.',
        links: [['/method#grid', 'Method, the grid']],
    },
    {
        q: 'How are the dots on the map created?',
        a: 'Each dot is one cell-day placed at the centre of its 1 km cell, with the sensors that saw it, the number of detections, the radiative power and the first day of the period it burned. Orange: VIIRS only. Blue: MODIS only. Yellow: both. A dot is not "a fire": ten dots together can be one front, one dot alone can be an hour of agricultural burning.',
        links: [
            ['/explore', 'Explore'],
            ['/api-docs', 'API: /api/week'],
        ],
    },
    {
        q: 'How do you decide that a week is unusual?',
        a: 'Each week is compared with the same calendar week in every other year since 2000: the median is "normal", the median absolute deviation is the spread. A week is flagged when it is at least 3 deviations above the median, at least twice it, and has at least 10 cell-days. Elevated from 3 deviations, high from 5, extreme from 8. Only a harmonized record makes weeks before and after 2012 comparable.',
        links: [['/alerts', 'Unusual periods']],
    },
    {
        q: 'What does "times normal" mean?',
        a: 'The observed cell-days of a week or a date range divided by the median of the same dates in all the other years. ×11.5 means eleven and a half times the typical value for those dates, not eleven times last year.',
        links: [[`/areas/dominican-republic?week=2015-04-27`, 'Dominican Republic, April 2015']],
    },
    {
        q: 'How does the next-week outlook work, and why that date?',
        a: 'A ridge regression per area on 14 inputs: the last weeks of fire, the same-week median of other years, NASA POWER weather over 7 and 28 days, and the time of year. It is validated year by year since 2013 against climatology and persistence: one week ahead, 19–31% less error than climatology (r 0.72–0.89). The record lags a day or two, so when the one-week target week has already begun the card shows the week after it from a two-week model (13–19%, r 0.67–0.87) and keeps "this week" as a second line.',
        links: [
            ['/method#forecast', 'Method, forecasting'],
            [`/areas/dominican-republic#outlook`, 'Outlook card'],
        ],
    },
    {
        q: 'What does the AI do, and what does it never do?',
        a: 'It writes: the bulletin of each area and the reading across areas, from the computed facts as JSON, in English or Spanish. It never decides what is unusual, never sees raw data and never adds a number: every figure in its text must be one of the facts, or a fixed template is shown instead. Two writers, Claude (Anthropic API) and Gemma 4 on our own server, each the fallback for the other; every text says which one wrote it.',
        links: [['/ai', 'AI analysis']],
    },
    {
        q: 'Where does the weather come from?',
        a: 'NASA POWER: daily air temperature, relative humidity, rain and wind at the centre of each area since November 2000, free and without a key, refreshed every morning. NASA publishes it with a lag of a few days, so the weather shown is "through" a recent date.',
        links: [
            ['/data', 'Ingestion'],
            ['/api-docs', 'API: /api/areas/{area}/weather'],
        ],
    },
    {
        q: 'What happens when MODIS retires?',
        a: 'NASA plans to stop Terra MODIS around January 2027. Continuum simulates it: switch MODIS off in a chosen year, fit the ratio only on earlier days, rebuild later months from VIIRS alone and compare with the real MODIS record. Switched off in 2024, the Dominican Republic keeps its shape with monthly correlation 0.91 and 88% less daily error than a naive switch.',
        links: [[`/areas/dominican-republic#retire`, 'Retirement test']],
    },
    {
        q: 'Why does the record start in November 2000, and why 21.2 million detections?',
        a: 'Terra MODIS began delivering fire detections in November 2000. The 21.2 million are every NASA FIRMS detection inside the five areas shown (the raw archive was downloaded before the event, as the organizers allow); other zones can be loaded from the FIRMS archive on request.',
        links: [
            ['/data', 'Ingestion'],
            ['/explore', 'Request a zone'],
        ],
    },
    {
        q: 'Can I follow my own zone?',
        a: 'Draw it on Explore: inside the five areas you get its own fit, calendar and unusual weeks in seconds, and you can leave an email to be alerted when one of its weeks turns unusual. Outside them, request the zone and it is loaded from the NASA archive.',
        links: [
            ['/explore', 'Explore'],
            ['/alerts/subscribe', 'Fire alerts'],
        ],
    },
];
</script>

<template>
    <Head :title="t('Questions')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Questions') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{ t('What people ask when they first open Continuum, with the numbers to check each answer.') }}
        </p>

        <section v-if="example" class="mt-8 rounded-md border border-orange-300/60 bg-orange-50/60 p-5 dark:border-orange-900 dark:bg-orange-950/20">
            <p class="text-sm font-medium text-orange-700 dark:text-orange-400">{{ t('One cell, four steps') }}</p>
            <h2 class="mt-1 text-xl font-semibold tracking-tight">
                {{
                    t(':area, cell :lat N, :lon W, :date', {
                        area: example.area,
                        lat: number(example.cell.lat, 2),
                        lon: number(Math.abs(example.cell.lon), 2),
                        date: date(example.day),
                    })
                }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('Read live from the database, so these are the numbers the site uses today.') }}</p>

            <ol class="mt-5 grid gap-5 md:grid-cols-2">
                <li class="rounded-md border bg-background p-4">
                    <div class="text-sm font-medium">1 · {{ t('One grid') }}</div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t('Pixels of both sensors fall inside the same square kilometre:') }}</p>
                    <table class="mt-2 w-full text-xs tabular-nums">
                        <tbody>
                            <tr v-for="d in example.detections" :key="d.time + d.satellite" class="border-t">
                                <td class="py-1 uppercase">{{ d.instrument }}</td>
                                <td>{{ d.satellite }}</td>
                                <td>{{ d.time }} UTC</td>
                                <td>{{ number(d.latitude, 4) }}, {{ number(d.longitude, 4) }}</td>
                                <td class="text-right">{{ number(d.frp, 1) }} MW</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-2 text-sm">
                        <span v-for="c in example.cell_days" :key="c.instrument" class="mr-3 uppercase"
                            >{{ c.instrument }}: <b>1</b> <span class="normal-case">{{ t('cell-day') }}</span></span
                        >
                    </p>
                </li>
                <li class="rounded-md border bg-background p-4">
                    <div class="text-sm font-medium">2 · {{ t('One count per sensor') }}</div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('All cells of the area that day, each sensor with all its satellites merged:') }}
                    </p>
                    <p class="mt-2 text-sm tabular-nums">
                        MODIS <b>{{ number(example.totals.modis?.cell_days) }}</b> {{ t('cell-days') }} ({{
                            number(example.totals.modis?.detections)
                        }}
                        {{ t('detections') }}) · VIIRS <b>{{ number(example.totals.viirs?.cell_days) }}</b> {{ t('cell-days') }} ({{
                            number(example.totals.viirs?.detections)
                        }}
                        {{ t('detections') }})
                    </p>
                </li>
                <li v-if="example.ratio_month && example.harmonized" class="rounded-md border bg-background p-4">
                    <div class="text-sm font-medium">3 · {{ t('One ratio') }}</div>
                    <p class="mt-1 text-sm tabular-nums text-muted-foreground">
                        {{
                            t('MODIS ÷ VIIRS for this month, fitted on :n days both sensors flew: :r [:lo–:hi]; whole year :y.', {
                                n: number(example.ratio_month.days),
                                r: number(example.ratio_month.ratio, 3),
                                lo: number(example.ratio_month.low, 3),
                                hi: number(example.ratio_month.high, 3),
                                y: number(example.ratio_year ?? 0, 3),
                            })
                        }}
                    </p>
                    <p class="mt-2 text-sm tabular-nums">
                        {{
                            t(
                                'Both flew that day, so the MODIS-scale record keeps MODIS’s own :m. A VIIRS-only day would be converted: :v × :r ≈ :c, marked as estimated. A naive merge would have written :v, with no more fire.',
                                {
                                    m: number(example.harmonized.modis),
                                    v: number(example.harmonized.viirs),
                                    r: number(example.ratio_month.ratio, 3),
                                    c: number(Math.round((example.harmonized.viirs ?? 0) * example.ratio_month.ratio)),
                                },
                            )
                        }}
                    </p>
                </li>
                <li v-if="example.validation" class="rounded-md border bg-background p-4">
                    <div class="text-sm font-medium">4 · {{ t('The test') }}</div>
                    <p class="mt-1 text-sm tabular-nums text-muted-foreground">
                        {{
                            example.held_out
                                ? t('This day was held out (overlap day :i of :n): the ratio never saw it.', {
                                      i: number((example.overlap_index ?? 0) + 1),
                                      n: number(example.overlap_days),
                                  })
                                : t('This day was a training day (overlap day :i of :n); one of every four days is held out.', {
                                      i: number((example.overlap_index ?? 0) + 1),
                                      n: number(example.overlap_days),
                                  })
                        }}
                    </p>
                    <p class="mt-2 text-sm tabular-nums">
                        {{
                            t('On the :n held-out days: daily error :a vs :b with a naive merge, :p less, correlation :c.', {
                                n: number(example.validation.test_days),
                                a: number(example.validation.mae, 2),
                                b: number(example.validation.naive_mae, 2),
                                p: percent(example.validation.improvement),
                                c: r(example.validation.correlation),
                            })
                        }}
                    </p>
                </li>
            </ol>
        </section>

        <div class="mt-10 grid gap-5 md:grid-cols-2">
            <article v-for="item in questions" :key="item.q" class="rounded-md border p-5">
                <h2 class="font-semibold">{{ t(item.q) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t(item.a) }}</p>
                <p class="mt-3 flex flex-wrap gap-x-4 text-sm">
                    <Link v-for="[href, label] in item.links" :key="href" :href="href" class="underline-offset-4 hover:underline"
                        >{{ t(label) }} →</Link
                    >
                </p>
            </article>
        </div>
    </SiteLayout>
</template>
