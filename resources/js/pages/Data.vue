<script setup lang="ts">
import Pager from '@/components/fire/Pager.vue';
import type { Status } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, dateRange, dateTime, number, t } from '@/lib/i18n';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

type Run = {
    id: number;
    feed: string;
    status: 'done' | 'failed' | 'running';
    started_at: string;
    seconds: number | null;
    read: number | null;
    kept: number | null;
    inserted: number | null;
    from: string | null;
    to: string | null;
    error: string | null;
};

const props = defineProps<{
    status: Status;
    freshness: { last_ingest: { at: string } | null; processed_at: string | null };
    feeds: Record<string, string>;
    filters: { feed: string; status: string };
    totals: { runs: number; failed: number; inserted: number; since: string | null };
    runs: Run[];
    page: number;
    pages: number;
    perPage: number;
    total: number;
}>();

const FEEDS: Record<string, string> = { modis: 'MODIS · Terra and Aqua', noaa20: 'VIIRS · NOAA-20', noaa21: 'VIIRS · NOAA-21' };
const SATELLITES: Record<string, string> = { T: 'Terra', A: 'Aqua', N: 'Suomi NPP', N20: 'NOAA-20', N21: 'NOAA-21' };
const STATUS: Record<Run['status'], string> = { done: 'Done', failed: 'Failed', running: 'Running' };

function filter(next: Partial<{ feed: string; status: string; page: number }>) {
    const query = { ...props.filters, ...next };
    router.get('/data', Object.fromEntries(Object.entries(query).filter(([, v]) => v)), { preserveScroll: true, preserveState: true });
}

let timer: number | undefined;
onMounted(() => {
    timer = window.setInterval(() => router.reload({ only: ['runs', 'totals', 'freshness', 'total', 'pages'] }), 60_000);
});
onUnmounted(() => window.clearInterval(timer));

function since(iso: string): string {
    const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60_000);
    if (minutes < 60) return t(':n min ago', { n: String(minutes) });
    if (minutes < 48 * 60) return t(':n h ago', { n: String(Math.round(minutes / 60)) });
    return dateTime(iso);
}
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

        <dl class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Pixels stored') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number(status.detections) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Completed pulls') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number(totals.runs) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Failed pulls') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums" :class="totals.failed && 'text-red-600'">{{ number(totals.failed) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Last processed') }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ freshness.processed_at ? since(freshness.processed_at) : '—' }}</dd>
            </div>
        </dl>

        <section class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 class="font-semibold">{{ t(':n pulls from NASA FIRMS', { n: number(total) }) }}</h2>
                <div class="flex flex-wrap gap-2 text-sm">
                    <div class="flex rounded-md border p-0.5">
                        <button
                            v-for="[value, label] in [['', t('All feeds')], ...Object.entries(FEEDS)]"
                            :key="value"
                            class="rounded px-3 py-1"
                            :class="filters.feed === value ? 'bg-foreground text-background' : 'text-muted-foreground'"
                            @click="filter({ feed: value, page: 1 })"
                        >
                            {{ label }}
                        </button>
                    </div>
                    <div class="flex rounded-md border p-0.5">
                        <button
                            v-for="[value, label] in [
                                ['', t('All')],
                                ['done', t('Done')],
                                ['failed', t('Failed')],
                            ]"
                            :key="value"
                            class="rounded px-3 py-1"
                            :class="filters.status === value ? 'bg-foreground text-background' : 'text-muted-foreground'"
                            @click="filter({ status: value, page: 1 })"
                        >
                            {{ label }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 font-normal">{{ t('Status') }}</th>
                            <th class="font-normal">{{ t('Started') }}</th>
                            <th class="font-normal">{{ t('Feed') }}</th>
                            <th class="font-normal">{{ t('Dates') }}</th>
                            <th class="text-right font-normal">{{ t('Pixels read') }}</th>
                            <th class="text-right font-normal">{{ t('In the areas') }}</th>
                            <th class="text-right font-normal">{{ t('New') }}</th>
                            <th class="text-right font-normal">{{ t('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody class="tabular-nums">
                        <tr v-for="r in runs" :key="r.id" class="border-t">
                            <td class="py-2">
                                <span class="inline-flex items-center gap-1.5">
                                    <span
                                        class="size-1.5 rounded-full"
                                        :class="r.status === 'done' ? 'bg-emerald-500' : r.status === 'failed' ? 'bg-red-500' : 'bg-orange-500'"
                                    />
                                    {{ t(STATUS[r.status]) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">{{ since(r.started_at) }}</td>
                            <td class="whitespace-nowrap">{{ FEEDS[r.feed] ?? r.feed }}</td>
                            <template v-if="r.status === 'failed'">
                                <td colspan="5" class="text-right text-red-600">{{ r.error }}</td>
                            </template>
                            <template v-else>
                                <td class="whitespace-nowrap text-muted-foreground">{{ r.from && r.to ? dateRange(r.from, r.to) : '—' }}</td>
                                <td class="text-right">{{ number(r.read) }}</td>
                                <td class="text-right">{{ number(r.kept) }}</td>
                                <td class="text-right font-medium">{{ number(r.inserted) }}</td>
                                <td class="text-right text-muted-foreground">{{ r.seconds === null ? '…' : `${number(r.seconds)} s` }}</td>
                            </template>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!runs.length" class="mt-3 text-sm text-muted-foreground">{{ t('No pulls match these filters.') }}</p>
            </div>
            <Pager :page="page" :pages="pages" :total="total" :per-page="perPage" @go="(p) => filter({ page: p })" />
            <p v-if="totals.since" class="mt-3 text-xs text-muted-foreground">
                {{ t('Every pull since :date is listed; the page refreshes itself every minute.', { date: dateTime(totals.since) }) }}
            </p>
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
                {{ t('Record: :range.', { range: dateRange(status.first_day, status.last_day) }) }}
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
