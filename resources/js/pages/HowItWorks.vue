<script setup lang="ts">
import type { Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { number, t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ status: Status }>();

const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
const modis = computed(() => props.status.instruments.find((i) => i.instrument === 'modis'));

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

const deploy = [
    ['GitHub', 'Code in github.com/elminson/continuum-spaceapps; every change goes to main.'],
    ['Spinnr', 'A push to main triggers Spinnr, which updates the server.'],
    ['Server', 'Nginx, PHP and PostgreSQL on one Linux server.'],
    ['Every hour', 'The scheduler pulls NASA FIRMS, re-processes recent days and pre-computes the pages.'],
];
</script>

<template>
    <Head :title="t('How it works')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('How it works') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('From a satellite pixel to a fire calendar, with live numbers.') }}</p>

        <ol class="mt-6 max-w-3xl">
            <li v-for="([title, body], i) in steps" :key="i" class="relative border-l-2 border-orange-300 pb-6 pl-6 last:pb-0">
                <span class="absolute -left-[9px] top-0 grid size-4 place-items-center rounded-full bg-orange-500" />
                <div class="font-medium">{{ title }}</div>
                <p class="text-sm text-muted-foreground">{{ body }}</p>
            </li>
        </ol>

        <h2 class="mt-10 text-lg font-semibold">{{ t('From code to the live site') }}</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-4">
            <div v-for="[title, body] in deploy" :key="title" class="rounded-lg border p-4">
                <div class="font-medium">{{ t(title) }}</div>
                <p class="mt-1 text-sm text-muted-foreground">{{ t(body) }}</p>
            </div>
        </div>
    </SiteLayout>
</template>
