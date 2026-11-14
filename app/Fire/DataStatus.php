<?php

namespace App\Fire;

use App\Models\Area;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** What data is loaded and how fresh it is. */
class DataStatus
{
    public const LAST_INGEST = 'ingest:last';

    /** @return array<string, mixed> */
    public function summary(): array
    {
        // Counting 21 million rows takes seconds: pages only read what fire:warm stored.
        return Cache::get('data:status') ?? [
            'detections' => 0, 'cell_days' => 0, 'instruments' => [], 'satellites' => [],
            'record_days' => 0, 'first_day' => Sensors::MODIS_START, 'last_day' => now()->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    public function refresh(): array
    {
        $summary = $this->compute();
        Cache::forever('data:status', $summary);

        return $summary;
    }

    /** @return array<string, mixed> */
    private function compute(): array
    {
        return (function () {
            $instruments = collect(DB::select('select instrument, count(*) as detections, min(acq_date)::text as first, max(acq_date)::text as last from detections group by instrument order by instrument'))
                ->keyBy('instrument');
            $cells = collect(DB::select('select instrument, count(*) as cell_days, sum(detections) as pixels from cell_days group by instrument'))->keyBy('instrument');
            $satellites = DB::select('select instrument, satellite, count(*) as detections, max(acq_date)::text as last from detections group by 1, 2 order by 1, 2');

            return [
                'detections' => (int) $instruments->sum('detections'),
                'cell_days' => (int) $cells->sum('cell_days'),
                'instruments' => $instruments->map(fn ($row, $name) => [
                    'instrument' => $name,
                    'detections' => (int) $row->detections,
                    'cell_days' => (int) ($cells[$name]->cell_days ?? 0),
                    'pixels_per_cell' => ($cells[$name]->cell_days ?? 0) > 0 ? round($cells[$name]->pixels / $cells[$name]->cell_days, 2) : null,
                    'first' => $row->first,
                    'last' => $row->last,
                ])->values()->all(),
                'satellites' => array_map(fn ($s) => ['instrument' => $s->instrument, 'satellite' => $s->satellite, 'detections' => (int) $s->detections, 'last' => $s->last], $satellites),
                'record_days' => (int) DB::scalar('select count(distinct acq_date) from coverage_days'),
                'first_day' => DB::scalar('select min(acq_date)::text from coverage_days'),
                'last_day' => DB::scalar('select max(acq_date)::text from coverage_days'),
            ];
        })();
    }

    /** @return array<string, mixed> */
    public function freshness(): array
    {
        return [
            'last_ingest' => Cache::get(self::LAST_INGEST),
            'processed_at' => Area::query()->max('processed_at'),
        ];
    }
}
