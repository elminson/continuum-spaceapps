<?php

namespace App\Fire;

use App\Models\Area;
use App\Models\AreaFit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One real 1 km cell on one real day, followed through the four steps, read from the database
 * so the numbers on the Questions page are the live ones.
 */
class WorkedExample
{
    public const DAY = '2015-04-28';

    public const CELL = [1910, -7138]; // cell ids: floor(lat × 100), floor(lon × 100) → 19.10 N, 71.38 W

    public function __construct(private Harmonizer $harmonizer, private DailyRecord $record) {}

    /** @return array<string, mixed>|null */
    public function for(Area $area): ?array
    {
        $example = Cache::remember("example:{$area->id}:".($area->processed_at?->timestamp ?? 0), now()->addDay(), function () use ($area) {
            [$lat, $lon] = self::CELL;
            $detections = DB::select('select instrument, satellite, acquired_at, latitude, longitude, frp from detections where acq_date = ? and floor(latitude*100) = ? and floor(longitude*100) = ? order by acquired_at', [self::DAY, $lat, $lon]);

            if ($detections === []) {
                return null;
            }

            $cellDays = DB::select('select instrument, detections, round(frp_sum::numeric, 1) as frp from cell_days where acq_date = ? and cell_lat = ? and cell_lon = ?', [self::DAY, $lat, $lon]);
            $totals = DB::select('select instrument, cell_days, detections from area_days where area_id = ? and acq_date = ?', [$area->id, self::DAY]);
            $month = AreaFit::query()->where('area_id', $area->id)->where('month', (int) substr(self::DAY, 5, 2))->first();
            $year = AreaFit::query()->where('area_id', $area->id)->where('month', 0)->first();
            $daily = collect($this->harmonizer->daily($area))->firstWhere('date', self::DAY);
            $overlap = Calibrate::overlap($this->record->for($area));
            $index = null;
            foreach ($overlap as $i => $p) {
                if ($p['date'] === self::DAY) {
                    $index = $i;
                }
            }

            return [
                'day' => self::DAY,
                'cell' => ['lat' => $lat / 100, 'lon' => $lon / 100],
                'detections' => array_map(fn ($d) => ['instrument' => $d->instrument, 'satellite' => FirmsFeed::satelliteName($d->satellite), 'time' => substr($d->acquired_at, 11, 5), 'latitude' => (float) $d->latitude, 'longitude' => (float) $d->longitude, 'frp' => (float) $d->frp], $detections),
                'cell_days' => array_map(fn ($r) => ['instrument' => $r->instrument, 'detections' => (int) $r->detections, 'frp' => (float) $r->frp], $cellDays),
                'totals' => collect($totals)->mapWithKeys(fn ($r) => [$r->instrument => ['cell_days' => (int) $r->cell_days, 'detections' => (int) $r->detections]])->all(),
                'ratio_month' => $month ? ['ratio' => (float) $month->ratio, 'low' => (float) $month->ratio_low, 'high' => (float) $month->ratio_high, 'days' => (int) $month->days] : null,
                'ratio_year' => $year ? (float) $year->ratio : null,
                'harmonized' => $daily,
                'overlap_index' => $index,
                'overlap_days' => count($overlap),
                'held_out' => $index !== null && $index % 4 === 3,
                'validation' => Calibrate::validate($overlap),
            ];
        });

        return $example === null ? null : ['area' => $area->label()] + $example;
    }
}
