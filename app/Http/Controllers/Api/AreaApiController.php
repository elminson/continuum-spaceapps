<?php

namespace App\Http\Controllers\Api;

use App\Fire\CompareDates;
use App\Fire\Explorer;
use App\Fire\Insights;
use App\Fire\Sensors;
use App\Http\Controllers\Controller;
use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AreaApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Area::query()->ordered()->get(['slug', 'name', 'west', 'south', 'east', 'north', 'processed_at'])]);
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

    public function season(Area $area, Insights $insights): JsonResponse
    {
        return response()->json(['area' => $area->slug, 'data' => $insights->season($area)]);
    }

    /** ?from=YYYY-MM-DD&to=YYYY-MM-DD against the same dates in every other year. */
    public function compare(Request $request, Area $area, Insights $insights): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.Sensors::MODIS_START],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
        ]);

        if (CarbonImmutable::parse($data['from'])->diffInDays($data['to']) >= CompareDates::MAX_DAYS) {
            return response()->json(['message' => 'Keep the range under a year.'], 422);
        }

        return response()->json(['area' => $area->slug, 'data' => $insights->compare($area, $data['from'], $data['to'])]);
    }

    public function alerts(Area $area, Insights $insights): JsonResponse
    {
        return response()->json(['area' => $area->slug, 'data' => $insights->alerts($area, 100)]);
    }

    public function retirement(Request $request, Area $area, Insights $insights): JsonResponse
    {
        return response()->json(['area' => $area->slug, 'data' => $insights->retirement($area, $request->integer('year') ?: null)]);
    }

    /** GeoJSON of the ~1 km cells with fire in the area during one week (both sensors). */
    public function week(Request $request, Area $area): JsonResponse
    {
        return $this->cells($area->cellBounds(), $request);
    }

    /** The same for any box: ?bbox=west,south,east,north&week=YYYY-MM-DD */
    public function boxWeek(Request $request): JsonResponse
    {
        $bbox = self::bbox($request);
        abort_if($bbox === null, 422, 'bbox=west,south,east,north');

        return $this->cells([(int) floor($bbox[1] * 100), (int) ceil($bbox[3] * 100), (int) floor($bbox[0] * 100), (int) ceil($bbox[2] * 100)], $request);
    }

    /** Instant analysis of a box inside the areas: ?bbox=west,south,east,north */
    public function explore(Request $request, Explorer $explorer): JsonResponse
    {
        $bbox = self::bbox($request);
        $problem = $bbox === null ? 'bbox=west,south,east,north' : Explorer::reject($bbox);

        if ($problem !== null) {
            return response()->json(['message' => $problem], 422);
        }

        return response()->json(['data' => $explorer->analyze($bbox)]);
    }

    /** @return array{0: float, 1: float, 2: float, 3: float}|null */
    public static function bbox(Request $request): ?array
    {
        $parts = explode(',', (string) $request->query('bbox', ''));

        return count($parts) === 4 && array_filter($parts, 'is_numeric') === $parts ? array_map('floatval', $parts) : null;
    }

    /**
     * Either ?week=YYYY-MM-DD (that Monday-to-Sunday week) or ?days=N (the last N days, up to 31).
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $bounds
     */
    private function cells(array $bounds, Request $request): JsonResponse
    {
        [$south, $north, $west, $east] = $bounds;
        $days = min(31, max(1, $request->integer('days')));

        if ($request->filled('days')) {
            $to = CarbonImmutable::today();
            $from = $to->subDays($days - 1);
        } else {
            $from = CarbonImmutable::parse((string) $request->query('week', now()->toDateString()))->startOfWeek();
            $to = $from->addDays(6);
        }

        $cells = DB::select(<<<'SQL'
            select cell_lat, cell_lon, string_agg(distinct instrument, ',') as instruments, sum(detections) as detections, round(sum(frp_sum)::numeric, 1) as frp
            from cell_days
            where acq_date between ? and ? and cell_lat >= ? and cell_lat < ? and cell_lon >= ? and cell_lon < ?
            group by cell_lat, cell_lon
            limit 20000
            SQL, [$from->toDateString(), $to->toDateString(), $south, $north, $west, $east]);

        return response()->json([
            'type' => 'FeatureCollection',
            'week' => $from->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'features' => array_map(fn (object $c) => [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [($c->cell_lon + 0.5) / 100, ($c->cell_lat + 0.5) / 100]],
                'properties' => ['sensors' => $c->instruments, 'detections' => (int) $c->detections, 'frp' => (float) $c->frp],
            ], $cells),
        ]);
    }

    private function scale(Request $request): string
    {
        return $request->query('scale') === Sensors::VIIRS ? Sensors::VIIRS : Sensors::MODIS;
    }
}
