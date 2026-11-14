<?php

namespace App\Fire;

use App\Models\Area;
use Illuminate\Support\Facades\DB;

/**
 * Every day of the record with each instrument's active cell-days in the
 * area: null when the instrument delivered no data that day, 0 when it
 * looked and saw no fire.
 */
class DailyRecord
{
    /** @return array<string, array{modis: ?int, viirs: ?int}> */
    public function for(Area $area): array
    {
        $rows = DB::select(<<<'SQL'
            select c.acq_date::text as day, c.instrument, coalesce(a.cell_days, 0) as cells
            from coverage_days c
            left join area_days a on a.area_id = ? and a.acq_date = c.acq_date and a.instrument = c.instrument
            order by c.acq_date
            SQL, [$area->id]);

        $days = [];

        foreach ($rows as $row) {
            $days[$row->day] ??= [Sensors::MODIS => null, Sensors::VIIRS => null];
            $days[$row->day][$row->instrument] = (int) $row->cells;
        }

        return $days;
    }

    /**
     * The same record for any box, read straight from the grid.
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $cells  south, north, west, east (north/east exclusive)
     * @return array<string, array{modis: ?int, viirs: ?int}>
     */
    public function forCells(array $cells): array
    {
        [$south, $north, $west, $east] = $cells;

        $rows = DB::select(<<<'SQL'
            select c.acq_date::text as day, c.instrument, coalesce(g.cells, 0) as cells
            from coverage_days c
            left join (
                select acq_date, instrument, count(*) as cells
                from cell_days
                where cell_lat >= ? and cell_lat < ? and cell_lon >= ? and cell_lon < ?
                group by acq_date, instrument
            ) g on g.acq_date = c.acq_date and g.instrument = c.instrument
            order by c.acq_date
            SQL, [$south, $north, $west, $east]);

        $days = [];

        foreach ($rows as $row) {
            $days[$row->day] ??= [Sensors::MODIS => null, Sensors::VIIRS => null];
            $days[$row->day][$row->instrument] = (int) $row->cells;
        }

        return $days;
    }
}
