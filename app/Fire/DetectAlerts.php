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
        $weeks = $this->weeks($area);
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
                    'area_id' => $area->id,
                    'week_start' => $start,
                    'observed' => round($observed, 1),
                    'typical' => round($typical, 1),
                    'spread' => round($spread, 2),
                    'score' => round($score, 2),
                    'severity' => $score >= 8 ? 'extreme' : ($score >= 5 ? 'high' : 'elevated'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($area, $alerts): void {
            Alert::query()->where('area_id', $area->id)->delete();

            foreach (array_chunk($alerts, 500) as $chunk) {
                Alert::query()->insert($chunk);
            }
        });

        return count($alerts);
    }

    /**
     * Complete weeks (Monday start, all 7 days with data) on the MODIS scale.
     *
     * @return array<string, float>
     */
    public function weeks(Area $area): array
    {
        $weeks = [];
        $days = [];

        foreach ($this->harmonizer->daily($area, Sensors::MODIS) as $day) {
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
