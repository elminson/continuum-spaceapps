<?php

namespace App\Fire;

use App\Models\Alert;
use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Flags weeks far above the same week of the year in every other year, on
 * the harmonized MODIS scale. Median and MAD keep one extreme year from
 * hiding another.
 */
class DetectAlerts
{
    public const MIN_SCORE = 3.0;

    public const MIN_CELL_DAYS = 10.0;

    public function __construct(private Harmonizer $harmonizer) {}

    public function __invoke(Area $area): int
    {
        $alerts = array_map(fn (array $a) => ['area_id' => $area->id, ...$a, 'created_at' => now(), 'updated_at' => now()], self::find($this->harmonizer->daily($area, Sensors::MODIS)));

        DB::transaction(function () use ($area, $alerts): void {
            Alert::query()->where('area_id', $area->id)->delete();

            foreach (array_chunk($alerts, 500) as $chunk) {
                Alert::query()->insert($chunk);
            }
        });

        return count($alerts);
    }

    /**
     * @param  list<array{date: string, value: ?float}>  $daily  harmonized MODIS-scale days
     * @return list<array{week_start: string, observed: float, typical: float, spread: float, score: float, severity: string}>
     */
    public static function find(array $daily): array
    {
        $weeks = self::weeks($daily);
        $byWeekOfYear = [];

        foreach ($weeks as $start => $value) {
            $byWeekOfYear[CarbonImmutable::parse($start)->isoWeek][$start] = $value;
        }

        $alerts = [];

        foreach ($weeks as $start => $observed) {
            $others = array_values(array_diff_key($byWeekOfYear[CarbonImmutable::parse($start)->isoWeek], [$start => true]));

            if (count($others) < 5) {
                continue;
            }

            $typical = Stats::median($others);
            $spread = max(Stats::mad($others), 0.1 * $typical, 1.0);
            $score = ($observed - $typical) / $spread;

            if ($score >= self::MIN_SCORE && $observed >= self::MIN_CELL_DAYS && $observed >= 2 * $typical) {
                $alerts[] = [
                    'week_start' => (string) $start,
                    'observed' => round($observed, 1),
                    'typical' => round($typical, 1),
                    'spread' => round($spread, 2),
                    'score' => round($score, 2),
                    'severity' => $score >= 8 ? 'extreme' : ($score >= 5 ? 'high' : 'elevated'),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Complete weeks (Monday start, all 7 days with data).
     *
     * @param  list<array{date: string, value: ?float}>  $daily
     * @return array<string, float>
     */
    public static function weeks(array $daily): array
    {
        $weeks = [];
        $days = [];

        foreach ($daily as $day) {
            if ($day['value'] === null) {
                continue;
            }

            $start = CarbonImmutable::parse($day['date'])->startOfWeek()->toDateString();
            $weeks[$start] = ($weeks[$start] ?? 0) + $day['value'];
            $days[$start] = ($days[$start] ?? 0) + 1;
        }

        return array_filter($weeks, fn ($value, $start) => $days[$start] === 7, ARRAY_FILTER_USE_BOTH);
    }
}
