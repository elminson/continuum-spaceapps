<script setup lang="ts">
import { COLORS, date, number, t } from '@/lib/i18n';
import * as maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{ slug: string; week: string; bbox: number[] }>();
const el = ref<HTMLDivElement | null>(null);
const cells = ref<number | null>(null);
let map: maplibregl.Map | null = null;

async function load() {
    const data = await (await fetch(`/api/areas/${props.slug}/week?week=${props.week}`)).json();
    cells.value = data.features.length;
    (map?.getSource('cells') as maplibregl.GeoJSONSource | undefined)?.setData(data);
}

onMounted(() => {
    map = new maplibregl.Map({
        container: el.value!,
        bounds: [props.bbox[0], props.bbox[1], props.bbox[2], props.bbox[3]],
        attributionControl: { compact: true },
        style: {
            version: 8,
            sources: {
                gibs: {
                    type: 'raster',
                    tiles: [
                        'https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/BlueMarble_ShadedRelief_Bathymetry/default/GoogleMapsCompatible_Level8/{z}/{y}/{x}.jpeg',
                    ],
                    tileSize: 256,
                    maxzoom: 8,
                    attribution: 'NASA GIBS · Blue Marble',
                },
            },
            layers: [{ id: 'gibs', type: 'raster', source: 'gibs' }],
        },
    });

    map.on('load', () => {
        map!.addSource('cells', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
        map!.addLayer({
            id: 'cells',
            type: 'circle',
            source: 'cells',
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 4, 2, 9, 6],
                'circle-color': ['match', ['get', 'sensors'], 'modis', COLORS.modis, 'viirs', COLORS.viirs, '#facc15'],
                'circle-opacity': 0.85,
                'circle-stroke-width': 0.5,
                'circle-stroke-color': '#111827',
            },
        });
        load();
    });
});

watch(() => props.week, load);
onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div>
        <div class="mb-2 flex flex-wrap gap-4 text-xs text-muted-foreground">
            <span>{{ t('Week of :date', { date: date(week) }) }} · {{ t(':n cells with fire', { n: number(cells) }) }}</span>
            <span><span class="inline-block size-2 rounded-full bg-[#facc15]" /> {{ t('both sensors') }}</span>
            <span><span class="inline-block size-2 rounded-full bg-[#f97316]" /> {{ t('VIIRS only') }}</span>
            <span><span class="inline-block size-2 rounded-full bg-[#3b82f6]" /> {{ t('MODIS only') }}</span>
        </div>
        <div ref="el" class="h-96 w-full overflow-hidden rounded-lg border" />
    </div>
</template>
