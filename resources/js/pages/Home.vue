<script setup lang="ts">
import Spark from '@/components/fire/Spark.vue';
import type { AlertRow } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, number, percent, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    areas: {
        slug: string;
        name: string;
        first_month: string | null;
        last_month: string | null;
        spark: (number | null)[];
        improvement: number | null;
        latest_alert: AlertRow | null;
    }[];
    detections: number;
    topAlerts: AlertRow[];
}>();
</script>

<template>
    <Head :title="t('Fire calendars')" />
    <SiteLayout>
        <section class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight">{{ t('One fire calendar from 26 years of NASA satellites') }}</h1>
            <p class="mt-3 text-muted-foreground">
                {{
                    t(
                        'MODIS (since 2000) and VIIRS (since 2012) count the same fires very differently. Continuum puts both on one 1 km grid and one scale, so a busy fire season reads as a busy season, not as a new satellite.',
                    )
                }}
            </p>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ t(':count NASA FIRMS detections · updated every hour', { count: number(detections) }) }}
            </p>
        </section>

        <section class="mt-8 grid gap-4 sm:grid-cols-2">
            <Link
                v-for="area in areas"
                :key="area.slug"
                :href="`/areas/${area.slug}`"
                class="rounded-lg border p-4 transition hover:border-foreground/40"
            >
                <div class="font-medium">{{ area.name }}</div>
                <div class="text-xs text-muted-foreground">{{ area.first_month }} → {{ area.last_month }}</div>
                <Spark class="mt-3" :values="area.spark" />
                <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                    <span v-if="area.improvement !== null">{{ t('Error vs naive merge −:p', { p: percent(area.improvement) }) }}</span>
                    <span v-if="area.latest_alert">{{ t('Last unusual week: :date', { date: date(area.latest_alert.week_start) }) }}</span>
                </div>
            </Link>
        </section>

        <section v-if="topAlerts.length" class="mt-10">
            <h2 class="text-lg font-semibold">{{ t('Most unusual weeks on record') }}</h2>
            <ul class="mt-3 divide-y rounded-lg border">
                <li v-for="alert in topAlerts" :key="`${alert.slug}-${alert.week_start}`">
                    <Link :href="`/areas/${alert.slug}`" class="flex items-center justify-between gap-4 px-4 py-3 text-sm hover:bg-muted/50">
                        <span>{{ alert.area }} · {{ t('week of :date', { date: date(alert.week_start) }) }}</span>
                        <span class="tabular-nums">×{{ number(alert.times, 1) }} {{ t('normal') }}</span>
                    </Link>
                </li>
            </ul>
        </section>
    </SiteLayout>
</template>
