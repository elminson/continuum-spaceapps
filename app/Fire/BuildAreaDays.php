<?php

namespace App\Fire;

use App\Models\Area;
use Illuminate\Support\Facades\DB;

/** Daily cell-day totals per instrument inside one area. */
class BuildAreaDays
{
    public function __invoke(Area $area, string $from, string $to): void
    {
        [$south, $north, $west, $east] = $area->cellBounds();

        DB::transaction(function () use ($area, $from, $to, $south, $north, $west, $east): void {
            DB::delete('delete from area_days where area_id = ? and acq_date between ? and ?', [$area->id, $from, $to]);

            DB::insert(<<<'SQL'
                insert into area_days (area_id, acq_date, instrument, cell_days, detections, frp_sum)
                select ?, acq_date, instrument, count(*), sum(detections), sum(frp_sum)
                from cell_days
                where acq_date between ? and ?
                  and cell_lat >= ? and cell_lat < ? and cell_lon >= ? and cell_lon < ?
                group by acq_date, instrument
                SQL, [$area->id, $from, $to, $south, $north, $west, $east]);
        });
    }
}
