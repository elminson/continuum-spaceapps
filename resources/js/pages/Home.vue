<script setup lang="ts">
import AlertList from '@/components/fire/AlertList.vue';
import type { ForecastData } from '@/components/fire/ForecastCard.vue';
import OutlookButton from '@/components/fire/OutlookButton.vue';
import QuestionsAnswered, { type Answers } from '@/components/fire/QuestionsAnswered.vue';
import Spark from '@/components/fire/Spark.vue';
import type { AlertRow, Place, Status } from '@/components/fire/types';
import UseCases from '@/components/fire/UseCases.vue';
import WeekMap from '@/components/fire/WeekMap.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, dateRange, number, percent, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    outlook: ForecastData | null;
    areas: { slug: string; name: string; spark: (number | null)[]; improvement: number | null; latest_alert: AlertRow | null }[];
    status: Status;
    places: Place[];
    topAlerts: AlertRow[];
    answers: Answers | null;
}>();

const place = ref<Place>(props.places[0]);
const mode = ref<'unusual' | 'recent'>('unusual');
const week = computed(() => (mode.value === 'unusual' ? (place.value.top_week ?? null) : null));
const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
</script>

<template>
    <Head :title="t('Overview')" />
    <SiteLayout>
        <header class="flex flex-wrap items-start justify-between gap-x-8 gap-y-4">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-semibold tracking-tight">{{ t('Was this fire unusual?') }}</h1>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'Continuum puts every NASA MODIS and VIIRS fire detection since November 2000 on one 1 km grid and one scale, so any week can be compared with the same week in every other year.',
                        )
                    }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 text-sm">
                <Link href="/explore?place=dominican-republic" class="rounded-md bg-foreground px-3 py-2 text-background">{{
                    t('Explore the Dominican Republic')
                }}</Link>
                <OutlookButton v-if="outlook" :forecast="outlook" href="/areas/dominican-republic#outlook" />
                <Link href="/how-it-works" class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">{{
                    t('How it works')
                }}</Link>
            </div>
        </header>

        <WeekMap class="mt-6" height="h-[62vh] min-h-[26rem]" :week="week" :days="30" :bbox="place.bbox" :times="place.top_times">
            <template #controls>
                <div class="inline-flex rounded-md bg-neutral-900/90 p-0.5 text-sm">
                    <button
                        class="rounded px-3 py-1"
                        :class="mode === 'unusual' ? 'bg-white text-neutral-900' : 'text-neutral-300'"
                        @click="mode = 'unusual'"
                    >
                        {{ t('Most unusual week') }}
                    </button>
                    <button
                        class="rounded px-3 py-1"
                        :class="mode === 'recent' ? 'bg-white text-neutral-900' : 'text-neutral-300'"
                        @click="mode = 'recent'"
                    >
                        {{ t('Last 30 days') }}
                    </button>
                </div>
            </template>
            <template #panel-top>
                <select
                    v-model="place"
                    class="mb-3 w-full rounded-md border border-neutral-700 bg-neutral-800 px-2 py-1.5 text-base font-semibold text-white"
                    :aria-label="t('Place')"
                >
                    <option v-for="p in places" :key="p.slug" :value="p">{{ p.name }}</option>
                </select>
            </template>
        </WeekMap>

        <QuestionsAnswered v-if="answers" class="mt-10" :answers="answers" />

        <UseCases class="mt-12" />

        <dl class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('NASA FIRMS detections') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number(status.detections) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('1 km cell-days') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number(status.cell_days) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('VIIRS pixels per 1 km cell') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">×{{ number(viirs?.pixels_per_cell, 1) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Record') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ t(':n days', { n: number(status.record_days) }) }}</dd>
                <dd class="text-xs text-muted-foreground">{{ dateRange(status.first_day, status.last_day) }}</dd>
            </div>
        </dl>

        <div class="mt-12 grid gap-10 lg:grid-cols-2">
            <section>
                <h2 class="font-semibold">{{ t('Areas of interest') }}</h2>
                <ul class="mt-3 text-sm">
                    <li v-for="area in areas" :key="area.slug">
                        <Link
                            :href="`/areas/${area.slug}`"
                            class="-mx-2 grid grid-cols-[1fr_6rem] items-center gap-4 rounded-md px-2 py-2 hover:bg-muted"
                        >
                            <span class="min-w-0">
                                <span class="block truncate">{{ area.name }}</span>
                                <span class="text-xs text-muted-foreground">
                                    <template v-if="area.improvement !== null">{{
                                        t('−:p error vs naive merge', { p: percent(area.improvement) })
                                    }}</template>
                                    <template v-if="area.latest_alert">
                                        · {{ t('last unusual week :date', { date: date(area.latest_alert.week_start) }) }}</template
                                    >
                                </span>
                            </span>
                            <Spark :values="area.spark" />
                        </Link>
                    </li>
                </ul>
            </section>
            <section>
                <h2 class="font-semibold">{{ t('Most unusual weeks on record') }}</h2>
                <AlertList class="mt-3" :alerts="topAlerts" />
                <Link href="/alerts" class="mt-2 inline-block text-sm text-muted-foreground hover:text-foreground">{{
                    t('All unusual periods →')
                }}</Link>
            </section>
        </div>
    </SiteLayout>
</template>
