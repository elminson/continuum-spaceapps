<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';

const steps = [
    [
        'One grid',
        'Every NASA FIRMS pixel goes into a 0.01° cell (about 1 km). A fire front that VIIRS reports with several 375 m pixels counts once, as MODIS would report it.',
    ],
    [
        'One count per sensor',
        'All satellites of a sensor are merged first (Terra + Aqua for MODIS; Suomi NPP, NOAA-20, NOAA-21 for VIIRS), so adding a satellite does not add fire.',
    ],
    [
        'One ratio for the physics',
        'VIIRS still sees smaller, cooler fires. On the days both sensors flew, Continuum fits MODIS ÷ VIIRS cell-days per calendar month, with a 95% interval from resampling days.',
    ],
    [
        'An honest test',
        'The ratio is fitted on three of every four overlap days and tested on the fourth, against a naive merge that just switches to VIIRS.',
    ],
    ['One scale', 'Choose MODIS or VIIRS. The chosen sensor is used whenever it flew; otherwise the other is converted and marked as estimated.'],
    [
        'Unusual weeks',
        'Each week is compared with the same week in every other year using the median and the median absolute deviation, so one extreme year cannot hide another.',
    ],
];

const limits = [
    'Active-fire data only records fires that were seen; clouds and smoke can hide fires.',
    'A day counts as observed when the sensor delivered any fire data in the processed areas that day.',
    'The ratio drifts over time: the MODIS retirement test shows about ±20% on totals two to three years ahead.',
    'Rectangular areas only.',
];
</script>

<template>
    <Head :title="t('Method')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('How Continuum harmonizes MODIS and VIIRS') }}</h1>
        <ol class="mt-6 max-w-3xl space-y-5">
            <li v-for="([title, body], i) in steps" :key="title">
                <div class="font-medium">{{ i + 1 }}. {{ t(title) }}</div>
                <p class="text-sm text-muted-foreground">{{ t(body) }}</p>
            </li>
        </ol>
        <h2 class="mt-10 text-lg font-semibold">{{ t('Limits') }}</h2>
        <ul class="mt-3 max-w-3xl list-disc space-y-1 pl-5 text-sm text-muted-foreground">
            <li v-for="limit in limits" :key="limit">{{ t(limit) }}</li>
        </ul>
        <h2 class="mt-10 text-lg font-semibold">{{ t('Open data and API') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
            {{ t('Data: NASA FIRMS active fires, MODIS Collection 6.1 and VIIRS 375 m. JSON:') }}
            <code>/api/areas</code>, <code>/api/areas/{slug}/monthly?scale=modis</code>, <code>/api/areas/{slug}/alerts</code>,
            <code>/api/areas/{slug}/retirement?year=2024</code>.
        </p>
    </SiteLayout>
</template>
