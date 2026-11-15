<script setup lang="ts">
import { COLORS, t } from '@/lib/i18n';
import { maplibregl, nasaMap } from '@/lib/map';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Place = { slug: string; name: string; bbox: number[] };

const props = defineProps<{ areas: Place[]; places: Place[]; boxes: Place[]; modelValue: number[] | null }>();
const emit = defineEmits<{ 'update:modelValue': [bbox: number[] | null] }>();

const el = ref<HTMLDivElement | null>(null);
const drawing = ref(false);
let map: maplibregl.Map | null = null;
let dragStart: [number, number] | null = null;

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

function draw(b: number[] | null) {
    (map?.getSource('box') as maplibregl.GeoJSONSource | undefined)?.setData({ type: 'FeatureCollection', features: b ? [rect(b)] : [] });
}

function pick(value: string) {
    const [kind, slug] = value.split(':');
    const list = kind === 'place' ? props.places : kind === 'box' ? props.boxes : props.areas;
    const found = list.find((p) => p.slug === slug);
    if (!found) return;
    emit('update:modelValue', [...found.bbox]);
    map?.fitBounds([found.bbox[0], found.bbox[1], found.bbox[2], found.bbox[3]], { padding: 40, duration: 0 });
}

function startDrawing() {
    drawing.value = true;
    emit('update:modelValue', null);
    map?.dragPan.disable();
    if (map) map.getCanvas().style.cursor = 'crosshair';
}

function stopDrawing() {
    drawing.value = false;
    map?.dragPan.enable();
    if (map) map.getCanvas().style.cursor = '';
}

watch(() => props.modelValue, draw);

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
        draw(props.modelValue);
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
        emit('update:modelValue', [Math.min(dragStart[0], x), Math.min(dragStart[1], y), Math.max(dragStart[0], x), Math.max(dragStart[1], y)]);
    };
    const finish = () => {
        if (!drawing.value || !dragStart) return;
        dragStart = null;
        stopDrawing();
    };
    map.on('mousedown', begin);
    map.on('mousemove', move);
    map.on('mouseup', finish);
    map.on('touchstart', begin);
    map.on('touchmove', move);
    map.on('touchend', finish);
});

onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <select
                class="rounded-md border bg-background px-2 py-1.5"
                :aria-label="t('Start from')"
                @change="pick(($event.target as HTMLSelectElement).value)"
            >
                <option value="">{{ t('Start from…') }}</option>
                <optgroup :label="t('Ready to open')">
                    <option v-for="p in places" :key="p.slug" :value="`place:${p.slug}`">{{ p.name }}</option>
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
                type="button"
                class="rounded-md px-3 py-1.5"
                :class="drawing ? 'bg-orange-600 text-white' : 'bg-foreground text-background'"
                @click="drawing ? stopDrawing() : startDrawing()"
            >
                {{ drawing ? t('Cancel') : t('Draw a box') }}
            </button>
            <span v-if="drawing" class="text-orange-700 dark:text-orange-400">{{ t('Drag on the map, inside a dashed area.') }}</span>
            <span v-else-if="modelValue" class="tabular-nums text-muted-foreground">{{ modelValue.map((v) => v.toFixed(2)).join(', ') }}</span>
        </div>
        <div ref="el" class="mt-2 h-[22rem] w-full overflow-hidden rounded-md" />
    </div>
</template>
