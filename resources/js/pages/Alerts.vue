<script setup lang="ts">
import AlertList from '@/components/fire/AlertList.vue';
import Pager from '@/components/fire/Pager.vue';
import type { AlertRow } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { number, t } from '@/lib/i18n';
import { maplibregl, nasaMap } from '@/lib/map';
import { Head, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Source = { slug: string; name: string; bbox: number[]; kind: 'area' | 'place'; counts: Record<'extreme' | 'high' | 'elevated', number> };

const props = defineProps<{
    filters: { area: string; severity: string | null; sort: 'recent' | 'strongest' };
    sources: Source[];
    total: number;
    alerts: AlertRow[];
    page: number;
    pages: number;
    perPage: number;
}>();

const SEVERITY_COLORS = { extreme: '#c2410c', high: '#ea580c', elevated: '#fb923c', none: '#64748b' };
const el = ref<HTMLDivElement | null>(null);
let map: maplibregl.Map | null = null;
let markers: maplibregl.Marker[] = [];

const worst = (s: Source) => (s.counts.extreme ? 'extreme' : s.counts.high ? 'high' : s.counts.elevated ? 'elevated' : 'none');
const flagged = (s: Source) => s.counts.extreme + s.counts.high + s.counts.elevated;

function filter(changes: Partial<typeof props.filters>, page = 1) {
    const next = { ...props.filters, ...changes, page: page > 1 ? page : null };
    router.get('/alerts', Object.fromEntries(Object.entries(next).filter(([, v]) => v)), { preserveScroll: true, preserveState: true });
}

function goTo(page: number) {
    filter({}, page);
    document.getElementById('flagged')?.scrollIntoView({ behavior: 'smooth' });
}

function drawMarkers() {
    markers.forEach((m) => m.remove());
    markers = props.sources.map((s) => {
        const dot = document.createElement('button');
        const size = s.kind === 'area' ? 46 : 32;
        const selected = props.filters.area === s.slug;
        dot.title = `${s.name}: ${flagged(s)}`;
        dot.textContent = String(flagged(s));
        dot.style.cssText = `width:${size}px;height:${size}px;border-radius:9999px;background:${SEVERITY_COLORS[worst(s)]}cc;color:#fff;font:600 ${s.kind === 'area' ? 13 : 11}px system-ui;border:${selected ? 3 : 2}px solid ${selected ? '#fff' : '#ffffff99'};cursor:pointer;box-shadow:0 1px 4px #0006`;
        dot.onclick = () => filter({ area: s.slug });
        const [w, so, e, n] = s.bbox;

        return new maplibregl.Marker({ element: dot }).setLngLat([(w + e) / 2, (so + n) / 2]).addTo(map!);
    });
}

function focus() {
    const s = props.sources.find((x) => x.slug === props.filters.area);
    const b = s?.bbox ?? [-85, -36, -40, 26];
    map?.fitBounds([b[0], b[1], b[2], b[3]], { padding: 40, duration: 400 });
}

onMounted(() => {
    map = nasaMap(el.value!, [-85, -36, -40, 26]);
    map.on('load', () => {
        map!.addSource('boxes', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: props.sources
                    .filter((s) => s.kind === 'area')
                    .map((s) => {
                        const [w, so, e, n] = s.bbox;
                        return {
                            type: 'Feature',
                            properties: {},
                            geometry: {
                                type: 'Polygon',
                                coordinates: [
                                    [
                                        [w, so],
                                        [e, so],
                                        [e, n],
                                        [w, n],
                                        [w, so],
                                    ],
                                ],
                            },
                        };
                    }),
            },
        });
        map!.addLayer({ id: 'boxes', type: 'line', source: 'boxes', paint: { 'line-color': '#ffffff', 'line-width': 1, 'line-dasharray': [2, 2] } });
        drawMarkers();
        focus();
    });
});

watch(
    () => props.filters.area,
    () => {
        drawMarkers();
        focus();
    },
);
onBeforeUnmount(() => map?.remove());
</script>

<template>
    <Head :title="t('Unusual periods')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Unusual periods') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Weeks far above the same week in every other year (median and MAD, MODIS scale). Only a harmonized record makes weeks before and after 2012 comparable.',
                )
            }}
        </p>

        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <select
                :value="filters.severity ?? ''"
                class="rounded-md border bg-background px-2 py-1.5"
                @change="filter({ severity: ($event.target as HTMLSelectElement).value || null })"
            >
                <option value="">{{ t('All severities') }}</option>
                <option value="extreme">{{ t('extreme') }}</option>
                <option value="high">{{ t('high') }}</option>
                <option value="elevated">{{ t('elevated') }}</option>
            </select>
            <select
                :value="filters.area"
                class="rounded-md border bg-background px-2 py-1.5"
                @change="filter({ area: ($event.target as HTMLSelectElement).value })"
            >
                <option value="">{{ t('All areas') }}</option>
                <optgroup :label="t('Areas of interest')">
                    <option v-for="s in sources.filter((x) => x.kind === 'area')" :key="s.slug" :value="s.slug">{{ s.name }}</option>
                </optgroup>
                <optgroup :label="t('Places')">
                    <option v-for="s in sources.filter((x) => x.kind === 'place')" :key="s.slug" :value="s.slug">{{ s.name }}</option>
                </optgroup>
            </select>
            <div class="inline-flex rounded-md border p-0.5">
                <button
                    class="rounded px-3 py-1"
                    :class="filters.sort === 'recent' ? 'bg-foreground text-background' : 'text-muted-foreground'"
                    @click="filter({ sort: 'recent' })"
                >
                    {{ t('Most recent') }}
                </button>
                <button
                    class="rounded px-3 py-1"
                    :class="filters.sort === 'strongest' ? 'bg-foreground text-background' : 'text-muted-foreground'"
                    @click="filter({ sort: 'strongest' })"
                >
                    {{ t('Strongest') }}
                </button>
            </div>
        </div>

        <section class="mt-4">
            <div class="relative">
                <div ref="el" class="h-[26rem] w-full overflow-hidden rounded-md" />
                <button
                    v-if="filters.area"
                    class="absolute left-3 top-3 rounded-md bg-background/90 px-2 py-1 text-sm shadow"
                    @click="filter({ area: '' })"
                >
                    ← {{ t('All areas') }}
                </button>
                <p class="absolute bottom-3 left-3 rounded-md bg-background/90 px-3 py-2 text-xs shadow">
                    {{ t('Number = flagged weeks · darker = a stronger worst week. Click to filter.') }}
                </p>
            </div>
        </section>

        <section id="flagged" class="mt-6 scroll-mt-4">
            <h2 class="font-semibold">{{ t(':n flagged weeks', { n: number(total) }) }}</h2>
            <AlertList v-if="alerts.length" class="mt-3" :alerts="alerts" />
            <p v-else class="mt-3 text-sm text-muted-foreground">{{ t('No unusual weeks match these filters.') }}</p>
            <Pager :page="page" :pages="pages" :total="total" :per-page="perPage" @go="goTo" />
        </section>
    </SiteLayout>
</template>
