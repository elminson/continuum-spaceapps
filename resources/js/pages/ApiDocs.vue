<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';

const base = typeof window === 'undefined' ? '' : window.location.origin;

const endpoints = [
    ['/api/areas', 'The areas and their bounding boxes.'],
    [
        '/api/areas/hispaniola/monthly?scale=modis',
        'Monthly cell-days: both sensors, the harmonized value, its interval, and the fit with its held-out test. scale=modis or viirs.',
    ],
    ['/api/areas/hispaniola/monthly.csv?scale=viirs', 'The same as CSV, with the method in the header.'],
    ['/api/areas/cuba/alerts', 'Unusual weeks of an area.'],
    ['/api/areas/cuba/retirement?year=2024', 'The MODIS retirement test for a cutoff year.'],
    ['/api/explore?bbox=-72.01,17.47,-68.32,19.93', 'Instant analysis of any box inside the areas (west,south,east,north).'],
    ['/api/week?bbox=-84.6,18.7,-74.2,24.9&week=2026-08-17', 'GeoJSON of the 1 km cells with fire in one week.'],
];
</script>

<template>
    <Head title="API" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Public API') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('Read-only JSON and CSV. No key, no login, 120 requests per minute.') }}</p>
        <ul class="mt-6 divide-y rounded-lg border">
            <li v-for="[path, body] in endpoints" :key="path" class="px-4 py-3">
                <a :href="path" target="_blank" class="break-all font-mono text-sm text-orange-700 hover:underline dark:text-orange-400"
                    >GET {{ base }}{{ path }}</a
                >
                <p class="mt-1 text-sm text-muted-foreground">{{ t(body) }}</p>
            </li>
        </ul>
    </SiteLayout>
</template>
