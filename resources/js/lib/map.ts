import * as maplibregl from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import 'maplibre-gl/dist/maplibre-gl.css';
import { COLORS } from './i18n';

maplibregl.setWorkerUrl(workerUrl);

/** A map over NASA GIBS Blue Marble imagery. */
export function nasaMap(container: HTMLElement, bounds: [number, number, number, number]): maplibregl.Map {
    const map = new maplibregl.Map({
        container,
        bounds,
        preserveDrawingBuffer: true,
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

    map.on('load', () => map.resize());
    new ResizeObserver(() => map.resize()).observe(container);

    return map;
}

/** Adds the layer of 1 km fire cells, coloured by which sensor saw them. Call after 'load'. */
export function addFireCells(map: maplibregl.Map): void {
    map.addSource('cells', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
    map.addLayer({
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
}

export type Period = { week: string } | { days: number };

/** Show only cells that had first burned by day `day` (0 = first day), or every cell when null. */
export function setFireDay(map: maplibregl.Map, day: number | null): void {
    if (map.getLayer('cells')) map.setFilter('cells', day === null ? null : ['<=', ['get', 'day'], day]);
}

/** Loads the fire cells of a box for one week or the last N days; returns how many cells burned. */
export async function loadFireCells(map: maplibregl.Map, bbox: number[], period: Period): Promise<number> {
    const query = 'week' in period ? `week=${period.week}` : `days=${period.days}`;
    const data = await (await fetch(`/api/week?bbox=${bbox.map((v) => v.toFixed(2)).join(',')}&${query}`)).json();
    (map.getSource('cells') as maplibregl.GeoJSONSource | undefined)?.setData(data);

    return data.features.length;
}

/**
 * Shows NASA GIBS true-colour imagery of one day (MODIS Terra) under the fire cells,
 * or goes back to Blue Marble when the date is null.
 */
export function setDailyImagery(map: maplibregl.Map, day: string | null): void {
    if (map.getLayer('daily')) map.removeLayer('daily');
    if (map.getSource('daily')) map.removeSource('daily');
    if (!day) return;

    map.addSource('daily', {
        type: 'raster',
        tiles: [
            `https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/${day}/GoogleMapsCompatible_Level9/{z}/{y}/{x}.jpg`,
        ],
        tileSize: 256,
        maxzoom: 9,
        attribution: `NASA GIBS · MODIS Terra ${day}`,
    });
    map.addLayer({ id: 'daily', type: 'raster', source: 'daily' }, map.getLayer('cells') ? 'cells' : undefined);
}

export { maplibregl };

/** Saves the map as a PNG with a caption strip under it. */
export function downloadMapImage(map: maplibregl.Map, lines: string[], filename: string): void {
    const source = map.getCanvas();
    const scale = 2;
    const pad = 16 * scale;
    const lineHeight = 22 * scale;
    const strip = pad * 2 + lineHeight * lines.length;
    const canvas = document.createElement('canvas');
    canvas.width = source.width;
    canvas.height = source.height + strip;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(source, 0, 0);
    ctx.fillStyle = '#17191c';
    ctx.fillRect(0, source.height, canvas.width, strip);
    ctx.fillStyle = '#f2f1ee';
    ctx.font = `${15 * scale}px Instrument Sans, system-ui, sans-serif`;
    lines.forEach((line, i) => {
        if (i === lines.length - 1) ctx.fillStyle = '#9aa0a8';
        ctx.fillText(line, pad, source.height + pad + lineHeight * (i + 0.8));
    });
    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = filename;
    a.click();
}
