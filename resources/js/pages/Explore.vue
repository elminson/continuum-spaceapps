<script setup lang="ts">
import CalendarHeatmap from '@/components/fire/CalendarHeatmap.vue';
import FireLegend from '@/components/fire/FireLegend.vue';
import Kpi from '@/components/fire/Kpi.vue';
import Loading from '@/components/fire/Loading.vue';
import MonthlyChart from '@/components/fire/MonthlyChart.vue';
import Pager from '@/components/fire/Pager.vue';
import TimesNormal from '@/components/fire/TimesNormal.vue';
import type { AlertRow, Fit, Month } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { COLORS, date, number, percent, r, t } from '@/lib/i18n';
import { addFireCells, loadFireCells, maplibregl, nasaMap } from '@/lib/map';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Place = { slug: string; name: string; bbox: number[]; id?: number; group?: string };

const props = defineProps<{
    areas: Place[];
    places: Place[];
    boxes: Place[];
    open?: { place: string | null; week: string | null; bbox: number[] | null };
}>();
const page = usePage();
const user = computed(() => (page.props.auth as { user: unknown } | undefined)?.user ?? null);
const saveName = ref('');
const flash = computed(() => (page.props.flash as { status?: string } | undefined)?.status);
const outside = computed(() => error.value !== null && error.value === t('Draw the box inside one of the dashed areas.'));
const zone = useForm({ name: '', email: '', bbox: [] as number[] });
const alertForm = useForm({ name: '', email: '', phone: '', bbox: [] as number[] });

function requestZone() {
    zone.bbox = box.value ?? [];
    zone.post('/zones', { preserveScroll: true, onSuccess: () => zone.reset('name', 'email') });
}

function subscribe() {
    alertForm.bbox = box.value ?? [];
    if (!alertForm.name) alertForm.name = currentBox.value?.name ?? (box.value ?? []).map((v) => v.toFixed(2)).join(', ');
    alertForm.post('/subscriptions');
}

function save() {
    if (!box.value || !saveName.value.trim()) return;
    router.post(
        '/boxes',
        { name: saveName.value.trim(), bbox: box.value.map((v) => Number(v.toFixed(2))) },
        { preserveScroll: true, preserveState: true, onSuccess: () => (saveName.value = '') },
    );
}

function forget(b: Place) {
    router.delete(`/boxes/${b.id}`, { preserveScroll: true, preserveState: true, onSuccess: () => (currentBox.value = null) });
}

type Result = { available: boolean; bbox: number[]; fit?: Fit; monthly?: Month[]; alerts?: AlertRow[] };

const el = ref<HTMLDivElement | null>(null);
const drawing = ref(false);
let dragStart: [number, number] | null = null;
const box = ref<number[] | null>(null);
const result = ref<Result | null>(null);
const error = ref<string | null>(null);
const loading = ref(false);
const week = ref<string | null>(null);
const cells = ref<number | null>(null);
const loadingCells = ref(false);
const PER_PAGE = 15;
const alertPage = ref(1);
const allAlerts = computed(() => result.value?.alerts ?? []);
const pages = computed(() => Math.max(1, Math.ceil(allAlerts.value.length / PER_PAGE)));
const shown = computed(() => allAlerts.value.slice((alertPage.value - 1) * PER_PAGE, alertPage.value * PER_PAGE));

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

let request = 0;

async function analyze(openWeek?: string | null) {
    if (!box.value || loading.value) return;
    const mine = ++request;
    loading.value = true;
    error.value = null;
    week.value = null;
    cells.value = null;
    (map?.getSource('cells') as maplibregl.GeoJSONSource | undefined)?.setData({ type: 'FeatureCollection', features: [] });
    const response = await fetch(`/api/explore?bbox=${box.value.map((v) => v.toFixed(2)).join(',')}`);
    const body = await response.json();
    if (mine !== request) return;
    loading.value = false;

    if (!response.ok) {
        error.value = t(body.message);
        result.value = null;
        return;
    }

    result.value = body.data;
    alertPage.value = 1;
    await showWeek(openWeek ?? body.data.alerts?.[0]?.week_start ?? null);
}

function go(place: Place, openWeek?: string | null) {
    box.value = [...place.bbox];
    map?.fitBounds([place.bbox[0], place.bbox[1], place.bbox[2], place.bbox[3]], { padding: 40 });
    draw();
    analyze(openWeek);
}

const currentBox = ref<Place | null>(null);

function startFrom(value: string) {
    const [kind, slug] = value.split(':');
    currentBox.value = null;

    if (kind === 'place') go(props.places.find((p) => p.slug === slug)!);
    if (kind === 'box') {
        currentBox.value = props.boxes.find((b) => b.slug === slug) ?? null;
        if (currentBox.value) go(currentBox.value);
    }
    if (kind === 'area') pick(props.areas.find((a) => a.slug === slug)!);
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

        const start = props.places.find((p) => p.slug === props.open?.place);
        if (start) go(start, props.open?.week);
        else if (props.open?.bbox) go({ slug: 'open', name: '', bbox: props.open.bbox }, props.open?.week);
    });

    const point = (e: maplibregl.MapMouseEvent | maplibregl.MapTouchEvent): [number, number] => [e.lngLat.lng, e.lngLat.lat];
    const begin = (e: maplibregl.MapMouseEvent | maplibregl.MapTouchEvent) => {
        if (!drawing.value) return;
        e.preventDefault();
        dragStart = point(e);
    };
    const move = (e: maplibregl.MapMouseEvent | maplibregl.MapTouchEvent) => {
        if (!drawing.value || !dragStart) return;
        const [x, y] = point(e);
        box.value = [Math.min(dragStart[0], x), Math.min(dragStart[1], y), Math.max(dragStart[0], x), Math.max(dragStart[1], y)];
        draw();
    };
    const finish = () => {
        if (!drawing.value || !dragStart) return;
        dragStart = null;
        stopDrawing();
        if (box.value && box.value[2] - box.value[0] > 0.01 && box.value[3] - box.value[1] > 0.01) analyze();
    };
    map.on('mousedown', begin);
    map.on('mousemove', move);
    map.on('mouseup', finish);
    map.on('touchstart', begin);
    map.on('touchmove', move);
    map.on('touchend', finish);
});

function startDrawing() {
    drawing.value = true;
    currentBox.value = null;
    result.value = null;
    error.value = null;
    box.value = null;
    draw();
    map?.dragPan.disable();
    map!.getCanvas().style.cursor = 'crosshair';
}

function stopDrawing() {
    drawing.value = false;
    map?.dragPan.enable();
    if (map) map.getCanvas().style.cursor = '';
}

onBeforeUnmount(() => map?.remove());
</script>

<template>
    <Head :title="t('Explore')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Explore any box') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{ t('Continuum fits and tests its own MODIS/VIIRS ratio for any box inside the dashed areas and builds its 26-year calendar.') }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
            <select
                class="rounded-md border bg-background px-2 py-1.5 disabled:opacity-50"
                :disabled="loading"
                :aria-label="t('Start from')"
                @change="startFrom(($event.target as HTMLSelectElement).value)"
            >
                <option value="">{{ t('Start from…') }}</option>
                <optgroup :label="t('Ready to open')">
                    <option v-for="p in places.filter((x) => x.group !== 'province')" :key="p.slug" :value="`place:${p.slug}`">{{ p.name }}</option>
                </optgroup>
                <optgroup :label="t('Provinces of the Dominican Republic')">
                    <option v-for="p in places.filter((x) => x.group === 'province')" :key="p.slug" :value="`place:${p.slug}`">{{ p.name }}</option>
                </optgroup>
                <optgroup v-if="boxes.length" :label="t('My boxes')">
                    <option v-for="b in boxes" :key="b.slug" :value="`box:${b.slug}`">{{ b.name }}</option>
                </optgroup>
                <optgroup :label="t('Areas of interest')">
                    <option v-for="a in areas" :key="a.slug" :value="`area:${a.slug}`">{{ a.name }}</option>
                </optgroup>
            </select>
            <span class="text-muted-foreground">{{ t('or') }}</span>
            <button
                class="rounded-md px-3 py-1.5 disabled:opacity-50"
                :class="drawing ? 'bg-orange-600 text-white' : 'bg-foreground text-background'"
                :disabled="loading"
                @click="drawing ? stopDrawing() : startDrawing()"
            >
                {{ drawing ? t('Cancel') : t('Draw a box') }}
            </button>
            <span v-if="drawing" class="text-orange-700 dark:text-orange-400">{{ t('Drag on the map, inside a dashed area.') }}</span>
            <button v-if="currentBox" class="ml-auto text-muted-foreground hover:text-red-600" @click="forget(currentBox)">
                {{ t('Delete “:name”', { name: currentBox.name }) }}
            </button>
        </div>

        <div v-if="loading" class="mt-3">
            <Loading
                :label="t('Fitting MODIS and VIIRS for this box over 26 years… the first time takes a few seconds; after that it is instant.')"
            />
        </div>
        <div v-else-if="result?.available" class="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
            <Loading v-if="loadingCells" :label="t('Loading fires…')" />
            <span v-else>
                {{ week ? t('Week of :date', { date: date(week) }) : t('Last :n days', { n: 30 }) }} ·
                {{ t(':n cells with fire', { n: number(cells) }) }}
            </span>
            <FireLegend />
        </div>
        <div ref="el" class="mt-2 h-[32rem] w-full overflow-hidden rounded-md" />

        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <span class="tabular-nums text-muted-foreground">
                {{ box ? box.map((v) => v.toFixed(2)).join(', ') : t('No box yet') }}
            </span>
            <button class="rounded-md bg-foreground px-3 py-1.5 text-background disabled:opacity-40" :disabled="!box || loading" @click="analyze()">
                {{ loading ? t('Analyzing…') : t('Analyze this box') }}
            </button>
            <span v-if="error" class="text-red-600">{{ error }}</span>
            <form v-if="user && box" class="ml-auto flex gap-2" @submit.prevent="save">
                <input v-model="saveName" maxlength="80" :placeholder="t('Name this box')" class="rounded-md border bg-background px-2 py-1.5" />
                <button class="rounded-md border px-3 py-1.5 hover:bg-muted" :disabled="!saveName.trim()">{{ t('Save') }}</button>
            </form>
            <Link v-else-if="!user" href="/login" class="ml-auto text-muted-foreground hover:text-foreground">{{ t('Sign in to save boxes') }}</Link>
        </div>

        <p
            v-if="flash"
            class="mt-4 rounded-md border border-orange-300/60 bg-orange-50/60 px-3 py-2 text-sm dark:border-orange-900 dark:bg-orange-950/20"
        >
            {{ flash }}
        </p>

        <form v-if="outside && box" class="mt-4 rounded-md border p-4 text-sm" @submit.prevent="requestZone">
            <p class="font-medium">{{ t('This zone is not loaded yet.') }}</p>
            <p class="mt-1 text-muted-foreground">
                {{
                    t(
                        'Continuum holds the NASA FIRMS archive only for the dashed areas, so boxes inside them answer instantly. Any other zone can be loaded: NASA prepares its archive in minutes to an hour, then importing and harmonizing it here takes from 10 minutes to a few hours depending on size. Leave an email and we tell you when it is ready.',
                    )
                }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <input
                    v-model="zone.name"
                    required
                    maxlength="80"
                    :placeholder="t('Zone name')"
                    class="rounded-md border bg-background px-2 py-1.5"
                />
                <input v-model="zone.email" type="email" required :placeholder="t('Email')" class="rounded-md border bg-background px-2 py-1.5" />
                <button class="rounded-md bg-foreground px-3 py-1.5 text-background disabled:opacity-40" :disabled="zone.processing">
                    {{ t('Request this zone') }}
                </button>
            </div>
            <p v-if="zone.errors.bbox || zone.errors.email" class="mt-2 text-red-600">{{ zone.errors.bbox ?? zone.errors.email }}</p>
        </form>

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

            <form class="mt-4 flex flex-wrap items-end gap-2 rounded-md border p-4 text-sm" @submit.prevent="subscribe">
                <div class="mr-auto">
                    <p class="font-medium">{{ t('Get an email when a week in this box is unusual') }}</p>
                    <p class="text-muted-foreground">
                        {{ t('No account needed. Phone alerts are coming soon.') }}
                        <Link href="/alerts/subscribe#how" class="underline-offset-4 hover:underline">{{ t('How an alert is decided') }} →</Link>
                    </p>
                </div>
                <input
                    v-model="alertForm.email"
                    type="email"
                    required
                    :placeholder="t('Email')"
                    class="rounded-md border bg-background px-2 py-1.5"
                />
                <input
                    v-model="alertForm.phone"
                    type="tel"
                    maxlength="30"
                    :placeholder="t('Phone · coming soon')"
                    class="w-40 rounded-md border bg-background px-2 py-1.5"
                />
                <button class="rounded-md bg-foreground px-3 py-1.5 text-background disabled:opacity-40" :disabled="alertForm.processing">
                    {{ t('Alert me') }}
                </button>
                <p v-if="alertForm.errors.email || alertForm.errors.bbox" class="w-full text-red-600">
                    {{ alertForm.errors.email ?? alertForm.errors.bbox }}
                </p>
            </form>

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
                <ul v-if="result.alerts?.length" class="mt-3 text-sm">
                    <li
                        v-for="a in shown"
                        :key="a.week_start"
                        class="-mx-2 flex cursor-pointer items-center justify-between rounded-md px-2 py-2 hover:bg-muted"
                        :class="{ 'bg-muted': a.week_start === week }"
                        @click="showWeek(a.week_start)"
                    >
                        <span>{{ t('Week of :date', { date: date(a.week_start) }) }}</span>
                        <TimesNormal :times="a.times" :severity="a.severity" />
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-muted-foreground">{{ t('No unusual weeks.') }}</p>
                <Pager :page="alertPage" :pages="pages" :total="allAlerts.length" :per-page="PER_PAGE" @go="(p) => (alertPage = p)" />
                <button class="mt-2 text-sm text-muted-foreground hover:text-foreground" @click="showWeek(null)">
                    {{ t('Show the last 30 days on the map') }}
                </button>
            </section>
        </template>
    </SiteLayout>
</template>
