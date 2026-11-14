<script setup lang="ts">
import CalendarHeatmap from '@/components/fire/CalendarHeatmap.vue';
import FireLegend from '@/components/fire/FireLegend.vue';
import Kpi from '@/components/fire/Kpi.vue';
import Loading from '@/components/fire/Loading.vue';
import MonthlyChart from '@/components/fire/MonthlyChart.vue';
import type { AlertRow, Fit, Month } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { COLORS, date, number, percent, r, t } from '@/lib/i18n';
import { addFireCells, loadFireCells, maplibregl, nasaMap } from '@/lib/map';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Place = { slug: string; name: string; bbox: number[]; id?: number };

const props = defineProps<{ areas: Place[]; places: Place[]; boxes: Place[] }>();
const page = usePage();
const user = computed(() => (page.props.auth as { user: unknown } | undefined)?.user ?? null);
const saveName = ref('');

function save() {
    if (!box.value || !saveName.value.trim()) return;
    router.post(
        '/boxes',
        { name: saveName.value.trim(), bbox: box.value.map((v) => Number(v.toFixed(2))) },
        { preserveScroll: true, preserveState: true, onSuccess: () => (saveName.value = '') },
    );
}

function forget(b: Place) {
    router.delete(`/boxes/${b.id}`, { preserveScroll: true, preserveState: true });
}

type Result = { available: boolean; bbox: number[]; fit?: Fit; monthly?: Month[]; alerts?: AlertRow[] };

const el = ref<HTMLDivElement | null>(null);
const corners = ref<[number, number][]>([]);
const box = ref<number[] | null>(null);
const result = ref<Result | null>(null);
const error = ref<string | null>(null);
const loading = ref(false);
const week = ref<string | null>(null);
const cells = ref<number | null>(null);
const loadingCells = ref(false);

async function showWeek(start: string | null) {
    week.value = start;

    if (map && result.value?.available) {
        loadingCells.value = true;
        cells.value = await loadFireCells(map, result.value.bbox, start ? { week: start } : { days: 30 });
        loadingCells.value = false;
    }
}
let map: maplibregl.Map | null = null;

const rect = (b: number[]) => ({
    type: 'Feature' as const,
    properties: {},
    geometry: {
        type: 'Polygon' as const,
        coordinates: [
            [
                [b[0], b[1]],
                [b[2], b[1]],
                [b[2], b[3]],
                [b[0], b[3]],
                [b[0], b[1]],
            ],
        ],
    },
});

function draw() {
    const source = map?.getSource('box') as maplibregl.GeoJSONSource | undefined;
    source?.setData({ type: 'FeatureCollection', features: box.value ? [rect(box.value)] : [] });
}

async function analyze() {
    if (!box.value) return;
    loading.value = true;
    error.value = null;
    week.value = null;
    cells.value = null;
    (map?.getSource('cells') as maplibregl.GeoJSONSource | undefined)?.setData({ type: 'FeatureCollection', features: [] });
    const response = await fetch(`/api/explore?bbox=${box.value.map((v) => v.toFixed(2)).join(',')}`);
    const body = await response.json();
    loading.value = false;

    if (!response.ok) {
        error.value = t(body.message);
        result.value = null;
        return;
    }

    result.value = body.data;
    await showWeek(body.data.alerts?.[0]?.week_start ?? null);
}

function go(place: Place) {
    box.value = [...place.bbox];
    map?.fitBounds([place.bbox[0], place.bbox[1], place.bbox[2], place.bbox[3]], { padding: 40 });
    draw();
    analyze();
}

function pick(area: { bbox: number[] }) {
    const [w, s, e, n] = area.bbox;
    const dx = Math.min(1, (e - w) / 4);
    const dy = Math.min(1, (n - s) / 4);
    const cx = (w + e) / 2;
    const cy = (s + n) / 2;
    box.value = [cx - dx, cy - dy, cx + dx, cy + dy];
    map?.fitBounds([w, s, e, n], { padding: 20 });
    draw();
}

onMounted(() => {
    map = nasaMap(el.value!, [-85, -36, -40, 26]);

    map.on('load', () => {
        map!.addSource('areas', { type: 'geojson', data: { type: 'FeatureCollection', features: props.areas.map((a) => rect(a.bbox)) } });
        map!.addLayer({
            id: 'areas',
            type: 'line',
            source: 'areas',
            paint: { 'line-color': '#ffffff', 'line-width': 1.5, 'line-dasharray': [2, 2] },
        });
        map!.addSource('box', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
        map!.addLayer({ id: 'box-fill', type: 'fill', source: 'box', paint: { 'fill-color': COLORS.viirs, 'fill-opacity': 0.2 } });
        map!.addLayer({ id: 'box-line', type: 'line', source: 'box', paint: { 'line-color': COLORS.viirs, 'line-width': 2 } });
        addFireCells(map!);
    });

    map.on('click', (e) => {
        corners.value = corners.value.length === 1 ? [...corners.value, [e.lngLat.lng, e.lngLat.lat]] : [[e.lngLat.lng, e.lngLat.lat]];

        if (corners.value.length === 2) {
            const [[x1, y1], [x2, y2]] = corners.value;
            box.value = [Math.min(x1, x2), Math.min(y1, y2), Math.max(x1, x2), Math.max(y1, y2)];
            draw();
        }
    });
});

onBeforeUnmount(() => map?.remove());
</script>

<template>
    <Head :title="t('Explore')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Explore any box') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Click two corners on the map inside a dashed area, or start from an area below. Continuum fits and tests its own MODIS/VIIRS ratio for that box and builds its 26-year calendar. No login needed.',
                )
            }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-muted-foreground">{{ t('Ready to open') }}</span>
            <button v-for="p in places" :key="p.slug" class="rounded-md border px-2 py-1 hover:bg-muted" @click="go(p)">{{ p.name }}</button>
        </div>

        <div v-if="boxes.length" class="mt-2 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-muted-foreground">{{ t('My boxes') }}</span>
            <span v-for="b in boxes" :key="b.slug" class="inline-flex items-center rounded-md border">
                <button class="px-2 py-1 hover:bg-muted" @click="go(b)">{{ b.name }}</button>
                <button class="border-l px-1.5 py-1 text-muted-foreground hover:text-red-600" :aria-label="t('Delete')" @click="forget(b)">×</button>
            </span>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-muted-foreground">{{ t('Or start a box in') }}</span>
            <button v-for="a in areas" :key="a.slug" class="rounded-md border px-2 py-1 hover:bg-muted" @click="pick(a)">{{ a.name }}</button>
        </div>

        <div v-if="loading" class="mt-3"><Loading :label="t('Fitting MODIS and VIIRS for this box over 26 years…')" /></div>
        <div v-else-if="result?.available" class="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
            <Loading v-if="loadingCells" :label="t('Loading fires…')" />
            <span v-else>
                {{ week ? t('Week of :date', { date: date(week) }) : t('Last :n days', { n: 30 }) }} ·
                {{ t(':n cells with fire', { n: number(cells) }) }}
            </span>
            <FireLegend />
        </div>
        <div ref="el" class="mt-2 h-[32rem] w-full overflow-hidden rounded-lg border" />

        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <span class="tabular-nums text-muted-foreground">
                {{ box ? box.map((v) => v.toFixed(2)).join(', ') : t('No box yet') }}
            </span>
            <button class="rounded-md bg-foreground px-3 py-1.5 text-background disabled:opacity-40" :disabled="!box || loading" @click="analyze">
                {{ loading ? t('Analyzing…') : t('Analyze this box') }}
            </button>
            <span v-if="error" class="text-red-600">{{ error }}</span>
            <form v-if="user && box" class="ml-auto flex gap-2" @submit.prevent="save">
                <input v-model="saveName" maxlength="80" :placeholder="t('Name this box')" class="rounded-md border bg-background px-2 py-1.5" />
                <button class="rounded-md border px-3 py-1.5 hover:bg-muted" :disabled="!saveName.trim()">{{ t('Save') }}</button>
            </form>
            <Link v-else-if="!user" href="/login" class="ml-auto text-muted-foreground hover:text-foreground">{{ t('Sign in to save boxes') }}</Link>
        </div>

        <p v-if="result && !result.available" class="mt-6 text-sm text-muted-foreground">
            {{ t('Too little fire in this box to fit the two sensors. Try a larger box.') }}
        </p>

        <template v-if="result?.available && result.fit?.validation">
            <div class="mt-6 grid gap-3 sm:grid-cols-4">
                <Kpi
                    :label="t('MODIS ÷ VIIRS cell-days')"
                    :value="`×${number(result.fit.ratio, 3)}`"
                    :hint="t('95% interval :low–:high', { low: number(result.fit.low, 3), high: number(result.fit.high, 3) })"
                />
                <Kpi
                    :label="t('Error vs naive merge')"
                    :value="`−${percent(result.fit.validation.improvement)}`"
                    :hint="t('on :days held-out days', { days: number(result.fit.validation.test_days) })"
                />
                <Kpi
                    :label="t('Daily error')"
                    :value="number(result.fit.validation.mae, 1)"
                    :hint="t('naive merge: :naive cell-days', { naive: number(result.fit.validation.naive_mae, 1) })"
                />
                <Kpi :label="t('Correlation on held-out days')" :value="r(result.fit.validation.correlation)" />
            </div>

            <section class="mt-8">
                <h2 class="text-lg font-semibold">{{ t('Fire calendar') }}</h2>
                <CalendarHeatmap class="mt-3" :months="result.monthly ?? []" />
            </section>

            <section class="mt-8">
                <h2 class="text-lg font-semibold">{{ t('The two sensors, and the harmonized record') }}</h2>
                <MonthlyChart class="mt-3" :months="result.monthly ?? []" scale="modis" />
            </section>

            <section class="mt-8">
                <h2 class="text-lg font-semibold">{{ t('Unusual weeks') }}</h2>
                <ul v-if="result.alerts?.length" class="mt-3 divide-y rounded-lg border text-sm">
                    <li
                        v-for="a in result.alerts"
                        :key="a.week_start"
                        class="flex cursor-pointer justify-between px-4 py-2 tabular-nums hover:bg-muted/50"
                        :class="{ 'bg-muted': a.week_start === week }"
                        @click="showWeek(a.week_start)"
                    >
                        <span>{{ t('week of :date', { date: date(a.week_start) }) }}</span>
                        <span>×{{ number(a.times, 1) }} {{ t('normal') }}</span>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-muted-foreground">{{ t('No unusual weeks.') }}</p>
                <button class="mt-2 text-sm text-muted-foreground hover:text-foreground" @click="showWeek(null)">
                    {{ t('Show the last 30 days on the map') }}
                </button>
            </section>
        </template>
    </SiteLayout>
</template>
