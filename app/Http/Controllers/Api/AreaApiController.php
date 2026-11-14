<?php

namespace App\Http\Controllers\Api;

use App\Fire\Insights;
use App\Fire\Sensors;
use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AreaApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Area::query()->orderBy('id')->get(['slug', 'name', 'west', 'south', 'east', 'north', 'processed_at'])]);
    }

    public function monthly(Request $request, Area $area, Insights $insights): JsonResponse
    {
        $scale = $this->scale($request);

        return response()->json(['area' => $area->slug, 'scale' => $scale, 'unit' => 'active 1 km cell-days', 'fit' => $insights->fit($area), 'data' => $insights->monthly($area, $scale)]);
    }

    public function csv(Request $request, Area $area, Insights $insights): StreamedResponse
    {
        $scale = $this->scale($request);
        $rows = $insights->monthly($area, $scale);
        $fit = $insights->fit($area);

        return response()->streamDownload(function () use ($rows, $fit, $area, $scale) {
            $out = fopen('php://output', 'w');
            fwrite($out, "# Continuum · {$area->name} · harmonized monthly active 1 km cell-days on the ".strtoupper($scale)." scale\n");
            fwrite($out, '# Source: NASA FIRMS (MODIS C6.1, VIIRS 375 m). MODIS/VIIRS ratio '.($fit['ratio'] ?? 'n/a').' ['.($fit['low'] ?? '').', '.($fit['high'] ?? '')."] fitted on the overlap; estimated=1 marks converted values.\n");
            fputcsv($out, ['month', 'modis_cell_days', 'viirs_cell_days', 'harmonized', 'low', 'high', 'estimated']);

            foreach ($rows as $r) {
                fputcsv($out, [$r['month'], $r['modis'], $r['viirs'], $r['value'], $r['low'], $r['high'], $r['estimated'] ? 1 : 0]);
            }

            fclose($out);
        }, "continuum-{$area->slug}-{$scale}-monthly.csv", ['Content-Type' => 'text/csv']);
    }

    public function alerts(Area $area, Insights $insights): JsonResponse
    {
        return response()->json(['area' => $area->slug, 'data' => $insights->alerts($area, 100)]);
    }

    public function retirement(Request $request, Area $area, Insights $insights): JsonResponse
    {
        return response()->json(['area' => $area->slug, 'data' => $insights->retirement($area, $request->integer('year') ?: null)]);
    }

    private function scale(Request $request): string
    {
        return $request->query('scale') === Sensors::VIIRS ? Sensors::VIIRS : Sensors::MODIS;
    }
}
