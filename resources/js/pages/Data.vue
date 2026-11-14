<script setup lang="ts">
import Kpi from '@/components/fire/Kpi.vue';
import type { Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { number, t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';

defineProps<{
    status: Status;
    freshness: {
        last_ingest: { at: string; feeds: { feed: string; ok: boolean; read?: number; kept?: number; inserted?: number; error?: string }[] } | null;
        processed_at: string | null;
    };
    feeds: Record<string, string>;
}>();

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString() : '—');
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

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <Kpi :label="t('Last pull')" :value="when(freshness.last_ingest?.at ?? null)" />
            <Kpi :label="t('Last processed')" :value="when(freshness.processed_at)" />
            <Kpi :label="t('Record')" :value="`${status.first_day} → ${status.last_day}`" />
        </div>

        <section v-if="freshness.last_ingest" class="mt-8">
            <h2 class="text-lg font-semibold">{{ t('Last pull by feed') }}</h2>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-normal">{{ t('Feed') }}</th>
                        <th class="font-normal">{{ t('Pixels read') }}</th>
                        <th class="font-normal">{{ t('In the areas') }}</th>
                        <th class="font-normal">{{ t('New') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y tabular-nums">
                    <tr v-for="f in freshness.last_ingest.feeds" :key="f.feed">
                        <td class="py-2">{{ f.feed }}</td>
                        <template v-if="f.ok">
                            <td>{{ number(f.read) }}</td>
                            <td>{{ number(f.kept) }}</td>
                            <td>{{ number(f.inserted) }}</td>
                        </template>
                        <td v-else colspan="3" class="text-red-600">{{ f.error }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">{{ t('What is loaded') }}</h2>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-normal">{{ t('Sensor') }}</th>
                        <th class="font-normal">{{ t('Satellite') }}</th>
                        <th class="font-normal">{{ t('Detections') }}</th>
                        <th class="font-normal">{{ t('Latest day') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y tabular-nums">
                    <tr v-for="s in status.satellites" :key="s.instrument + s.satellite">
                        <td class="py-2">{{ s.instrument.toUpperCase() }}</td>
                        <td>{{ s.satellite }}</td>
                        <td>{{ number(s.detections) }}</td>
                        <td>{{ s.last }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-2 text-xs text-muted-foreground">
                {{ t('T = Terra, A = Aqua, N = Suomi NPP (stopped 2 November 2026), N20 = NOAA-20, N21 = NOAA-21.') }}
            </p>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-semibold">{{ t('Sources') }}</h2>
            <ul class="mt-2 space-y-1 break-all text-xs text-muted-foreground">
                <li v-for="(url, name) in feeds" :key="name">{{ name }}: {{ url }}</li>
            </ul>
            <p class="mt-2 text-xs text-muted-foreground">
                {{ t('The full 2000 → 2026 archive for the four areas was downloaded from NASA FIRMS before the event, as the organizers allow.') }}
            </p>
        </section>
    </SiteLayout>
</template>
