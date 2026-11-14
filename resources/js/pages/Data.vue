<script setup lang="ts">
import type { Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, dateRange, dateTime, number, t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';

defineProps<{
    status: Status;
    freshness: {
        last_ingest: { at: string; feeds: { feed: string; ok: boolean; read?: number; kept?: number; inserted?: number; error?: string }[] } | null;
        processed_at: string | null;
    };
    feeds: Record<string, string>;
}>();

const FEEDS: Record<string, string> = { modis: 'MODIS · Terra and Aqua', noaa20: 'VIIRS · NOAA-20', noaa21: 'VIIRS · NOAA-21' };
const SATELLITES: Record<string, string> = { T: 'Terra', A: 'Aqua', N: 'Suomi NPP', N20: 'NOAA-20', N21: 'NOAA-21' };
</script>

<template>
    <Head :title="t('Ingestion')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Ingestion') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Every hour Continuum pulls the last 24 hours of NASA FIRMS near-real-time fires for MODIS, NOAA-20 and NOAA-21, keeps the pixels inside the areas, and re-processes the recent days.',
                )
            }}
        </p>

        <dl class="mt-8 grid gap-6 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Last pull') }}</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums">{{ dateTime(freshness.last_ingest?.at) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Last processed') }}</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums">{{ dateTime(freshness.processed_at) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Record') }}</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums">{{ dateRange(status.first_day, status.last_day) }}</dd>
            </div>
        </dl>

        <section v-if="freshness.last_ingest" class="mt-10">
            <h2 class="font-semibold">{{ t('Last pull by feed') }}</h2>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-normal">{{ t('Feed') }}</th>
                        <th class="text-right font-normal">{{ t('Pixels read') }}</th>
                        <th class="text-right font-normal">{{ t('In the areas') }}</th>
                        <th class="text-right font-normal">{{ t('New') }}</th>
                    </tr>
                </thead>
                <tbody class="tabular-nums">
                    <tr v-for="f in freshness.last_ingest.feeds" :key="f.feed" class="border-t">
                        <td class="py-2">{{ FEEDS[f.feed] ?? f.feed }}</td>
                        <template v-if="f.ok">
                            <td class="text-right">{{ number(f.read) }}</td>
                            <td class="text-right">{{ number(f.kept) }}</td>
                            <td class="text-right">{{ number(f.inserted) }}</td>
                        </template>
                        <td v-else colspan="3" class="text-right text-red-600">{{ f.error }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="mt-10">
            <h2 class="font-semibold">{{ t('What is loaded') }}</h2>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-normal">{{ t('Satellite') }}</th>
                        <th class="font-normal">{{ t('Sensor') }}</th>
                        <th class="text-right font-normal">{{ t('Detections') }}</th>
                        <th class="text-right font-normal">{{ t('Latest day') }}</th>
                    </tr>
                </thead>
                <tbody class="tabular-nums">
                    <tr v-for="s in status.satellites" :key="s.instrument + s.satellite" class="border-t">
                        <td class="py-2">{{ SATELLITES[s.satellite] ?? s.satellite }}</td>
                        <td>{{ s.instrument.toUpperCase() }}</td>
                        <td class="text-right">{{ number(s.detections) }}</td>
                        <td class="text-right">{{ date(s.last) }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-2 text-xs text-muted-foreground">
                {{ t('Suomi NPP stopped sending data on 2 November 2026; NOAA-20 and NOAA-21 carry VIIRS on.') }}
            </p>
        </section>

        <section class="mt-10">
            <h2 class="font-semibold">{{ t('Sources') }}</h2>
            <ul class="mt-2 space-y-1 break-all text-xs text-muted-foreground">
                <li v-for="(url, name) in feeds" :key="name">
                    {{ FEEDS[name] ?? name }}: <a :href="url" class="underline-offset-4 hover:underline">{{ url }}</a>
                </li>
            </ul>
            <p class="mt-3 text-xs text-muted-foreground">
                {{ t('The full 2000 → 2026 archive for the four areas was downloaded from NASA FIRMS before the event, as the organizers allow.') }}
            </p>
        </section>
    </SiteLayout>
</template>
