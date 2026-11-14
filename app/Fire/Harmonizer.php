<?php

namespace App\Fire;

use App\Models\Area;
use App\Models\AreaFit;

/**
 * One continuous series on a single sensor's scale. The chosen sensor's own
 * count is used whenever it flew; otherwise the other sensor is converted
 * with the monthly ratio and the value is marked as estimated.
 */
class Harmonizer
{
    public function __construct(private DailyRecord $record) {}

    /**
     * @return list<array{date: string, modis: ?int, viirs: ?int, value: ?float, low: ?float, high: ?float, estimated: bool}>
     */
    public function daily(Area $area, string $scale = Sensors::MODIS): array
    {
        $fits = AreaFit::query()->where('area_id', $area->id)->get()
            ->mapWithKeys(fn (AreaFit $fit) => [$fit->month => ['ratio' => $fit->ratio, 'low' => $fit->ratio_low, 'high' => $fit->ratio_high, 'days' => $fit->days]])
            ->all();

        $series = [];

        foreach ($this->record->for($area) as $date => $day) {
            $series[] = ['date' => $date, 'modis' => $day['modis'], 'viirs' => $day['viirs'], ...self::convert($day, $fits, (int) substr($date, 5, 2), $scale)];
        }

        return $series;
    }

    /**
     * @param  array{modis: ?int, viirs: ?int}  $day
     * @param  array<int, array{ratio: float, low: float, high: float, days: int}>  $fits
     * @return array{value: ?float, low: ?float, high: ?float, estimated: bool}
     */
    public static function convert(array $day, array $fits, int $month, string $scale): array
    {
        $own = $day[$scale];
        $other = $scale === Sensors::MODIS ? $day[Sensors::VIIRS] : $day[Sensors::MODIS];

        if ($own !== null) {
            return ['value' => (float) $own, 'low' => (float) $own, 'high' => (float) $own, 'estimated' => false];
        }

        if ($other === null || $fits === []) {
            return ['value' => null, 'low' => null, 'high' => null, 'estimated' => false];
        }

        $f = Calibrate::factor($fits, $month);

        if ($scale === Sensors::MODIS) {
            return ['value' => $other * $f['ratio'], 'low' => $other * $f['low'], 'high' => $other * $f['high'], 'estimated' => true];
        }

        $divide = fn (float $r) => $r > 0 ? $other / $r : 0.0;

        return ['value' => $divide($f['ratio']), 'low' => $divide($f['high']), 'high' => $divide($f['low']), 'estimated' => true];
    }

    /**
     * Daily values summed by month.
     *
     * @return list<array{month: string, modis: ?int, viirs: ?int, value: ?float, low: ?float, high: ?float, estimated: bool}>
     */
    public function monthly(Area $area, string $scale = Sensors::MODIS): array
    {
        $months = [];

        foreach ($this->daily($area, $scale) as $day) {
            $key = substr($day['date'], 0, 7);
            $m = $months[$key] ?? ['month' => $key, 'modis' => null, 'viirs' => null, 'value' => null, 'low' => null, 'high' => null, 'estimated' => false];

            foreach (['modis', 'viirs', 'value', 'low', 'high'] as $field) {
                if ($day[$field] !== null) {
                    $m[$field] = ($m[$field] ?? 0) + $day[$field];
                }
            }

            $m['estimated'] = $m['estimated'] || $day['estimated'];
            $months[$key] = $m;
        }

        return array_map(function (array $m) {
            foreach (['value', 'low', 'high'] as $field) {
                $m[$field] = $m[$field] === null ? null : round($m[$field], 1);
            }

            return $m;
        }, array_values($months));
    }
}
