<script setup lang="ts">
import AlertList from '@/components/fire/AlertList.vue';
import Kpi from '@/components/fire/Kpi.vue';
import Spark from '@/components/fire/Spark.vue';
import type { AlertRow, Place, Status } from '@/components/fire/types';
import WeekMap from '@/components/fire/WeekMap.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, number, percent, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    areas: {
        slug: string;
        name: string;
        first_month: string | null;
        last_month: string | null;
        spark: (number | null)[];
        improvement: number | null;
        latest_alert: AlertRow | null;
    }[];
    status: Status;
    places: Place[];
    topAlerts: AlertRow[];
}>();

const place = ref<Place>(props.places[0]);
const today = new Date().toISOString().slice(0, 10);
const viirs = computed(() => props.status.instruments.find((i) => i.instrument === 'viirs'));
</script>

<template>
    <Head :title="t('Overview')" />
    <SiteLayout>
        <section class="rounded-xl border bg-gradient-to-br from-orange-50 to-background p-6 dark:from-orange-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">
                {{ t('NASA Space Apps 2026 · Harmonization of MODIS and VIIRS hot spots') }}
            </p>
            <h1 class="mt-2 max-w-3xl text-3xl font-semibold tracking-tight">{{ t('One continuous fire record, from November 2000 to today') }}</h1>
            <p class="mt-3 max-w-3xl text-muted-foreground">
                {{
                    t(
                        'MODIS (since 2000) and VIIRS (since 2012) count the same fires very differently. Continuum puts both on one 1 km grid and one scale, so a busy fire season reads as a busy season, not as a new satellite.',
                    )
                }}
            </p>
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <Link href="/explore" class="rounded-md bg-foreground px-3 py-2 text-background">{{ t('Explore the Dominican Republic') }}</Link>
                <Link href="/method" class="rounded-md border px-3 py-2 hover:bg-muted">{{ t('Method') }}</Link>
                <Link href="/api-docs" class="rounded-md border px-3 py-2 hover:bg-muted">API</Link>
            </div>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Kpi
                :label="t('NASA FIRMS detections')"
                :value="number(status.detections)"
                :hint="status.instruments.map((i) => `${i.instrument.toUpperCase()} ${number(i.detections)}`).join(' · ')"
            />
            <Kpi :label="t('1 km cell-days')" :value="number(status.cell_days)" :hint="t('pixels collapsed to one grid')" />
            <Kpi
                :label="t('VIIRS pixels per 1 km cell')"
                :value="`×${number(viirs?.pixels_per_cell, 1)}`"
                :hint="t('the inflation removed by gridding')"
            />
            <Kpi :label="t('Record')" :value="t(':n days', { n: number(status.record_days) })" :hint="`${status.first_day} → ${status.last_day}`" />
        </div>

        <section class="mt-6">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">{{ t('Fires this week · :place', { place: place.name }) }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Every 1 km cell where MODIS or VIIRS saw fire since Monday. New NASA data every hour.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-1 text-sm">
                    <button
                        v-for="p in places"
                        :key="p.slug"
                        class="rounded-md border px-2 py-1"
                        :class="p.slug === place.slug ? 'bg-foreground text-background' : 'hover:bg-muted'"
                        @click="place = p"
                    >
                        {{ p.name }}
                    </button>
                </div>
            </div>
            <WeekMap class="mt-3" :week="today" :bbox="place.bbox" />
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-2">
            <div>
                <h2 class="text-lg font-semibold">{{ t('Areas of interest') }}</h2>
                <div class="mt-3 grid gap-3">
                    <Link
                        v-for="area in areas"
                        :key="area.slug"
                        :href="`/areas/${area.slug}`"
                        class="rounded-lg border p-4 transition hover:border-foreground/40"
                    >
                        <div class="flex justify-between gap-2">
                            <span class="font-medium">{{ area.name }}</span>
                            <span v-if="area.improvement !== null" class="text-xs text-muted-foreground">{{
                                t('Error vs naive merge −:p', { p: percent(area.improvement) })
                            }}</span>
                        </div>
                        <Spark class="mt-2" :values="area.spark" />
                        <div v-if="area.latest_alert" class="text-xs text-muted-foreground">
                            {{ t('Last unusual week: :date', { date: date(area.latest_alert.week_start) }) }}
                        </div>
                    </Link>
                </div>
            </div>
            <div>
                <h2 class="text-lg font-semibold">{{ t('Most unusual weeks on record') }}</h2>
                <AlertList class="mt-3" :alerts="topAlerts" />
                <Link href="/alerts" class="mt-2 inline-block text-sm text-muted-foreground hover:text-foreground">{{
                    t('All unusual periods →')
                }}</Link>
            </div>
        </section>
    </SiteLayout>
</template>
