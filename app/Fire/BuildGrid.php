<?php

namespace App\Fire;

use Illuminate\Support\Facades\DB;

/**
 * Collapses raw pixels into ~1 km grid cells (0.01°) per day and instrument.
 * A VIIRS fire front reported by several 375 m pixels, or by several
 * satellites, becomes one cell-day, the same unit MODIS reports.
 */
class BuildGrid
{
    public function __invoke(string $from, string $to): void
    {
        DB::statement(<<<'SQL'
            insert into cell_days (instrument, acq_date, cell_lat, cell_lon, detections, frp_sum)
            select instrument, acq_date, floor(latitude * 100)::int, floor(longitude * 100)::int, count(*), sum(frp)
            from detections
            where acq_date between ? and ?
            group by 1, 2, 3, 4
            on conflict (instrument, acq_date, cell_lat, cell_lon)
            do update set detections = excluded.detections, frp_sum = excluded.frp_sum
            SQL, [$from, $to]);

        DB::statement(<<<'SQL'
            insert into coverage_days (instrument, acq_date)
            select distinct instrument, acq_date from detections where acq_date between ? and ?
            on conflict do nothing
            SQL, [$from, $to]);
    }
}
