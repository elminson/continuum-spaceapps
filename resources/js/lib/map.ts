import * as maplibregl from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import 'maplibre-gl/dist/maplibre-gl.css';

maplibregl.setWorkerUrl(workerUrl);

/** A map over NASA GIBS Blue Marble imagery. */
export function nasaMap(container: HTMLElement, bounds: [number, number, number, number]): maplibregl.Map {
    const map = new maplibregl.Map({
        container,
        bounds,
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

    return map;
}

export { maplibregl };
