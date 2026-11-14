<?php

namespace App\Fire;

/**
 * The typical fire season of a place: the median harmonized activity of
 * each calendar month over the complete years, and the run of months
 * around the peak that holds at least half of the peak month's activity.
 */
class FireSeason
{
    public const SHARE_OF_PEAK = 0.5;

    /**
     * @param  list<array{month: string, value: ?float}>  $monthly  harmonized months (YYYY-MM)
     * @return array<string, mixed>|null
     */
    public static function from(array $monthly, ?string $today = null): ?array
    {
        $today ??= now()->toDateString();
        $byYear = [];

        foreach ($monthly as $m) {
            if ($m['value'] !== null) {
                $byYear[(int) substr($m['month'], 0, 4)][(int) substr($m['month'], 5, 2)] = (float) $m['value'];
            }
        }

        // Complete years only, so a half year never drags a month down.
        $years = array_filter($byYear, fn (array $months) => count($months) === 12);

        if (count($years) < 3) {
            return null;
        }

        $climatology = [];

        for ($month = 1; $month <= 12; $month++) {
            $climatology[$month] = round(Stats::median(array_column($years, $month)), 1);
        }

        $peak = array_search(max($climatology), $climatology, true);
        $threshold = self::SHARE_OF_PEAK * $climatology[$peak];
        $season = [$peak];

        foreach ([-1, 1] as $step) {
            for ($i = 1; $i < 12; $i++) {
                $month = (($peak - 1 + $step * $i) % 12 + 12) % 12 + 1;

                if ($climatology[$month] < $threshold || in_array($month, $season, true)) {
                    break;
                }

                $season[] = $month;
            }
        }

        $start = self::edge($season, $peak, -1);
        $end = self::edge($season, $peak, 1);
        $total = array_sum($climatology);
        $current = (int) substr($today, 5, 2);

        return [
            'months' => array_values($climatology),
            'season' => array_values(array_map('intval', $season)),
            'start' => $start,
            'peak' => (int) $peak,
            'end' => $end,
            'share' => $total > 0 ? round(array_sum(array_intersect_key($climatology, array_flip($season))) / $total, 3) : 0.0,
            'in_season' => in_array($current, $season, true),
            'current_month' => $current,
            'years' => count($years),
        ];
    }

    /** First (step -1) or last (step 1) month of the contiguous run around the peak. */
    private static function edge(array $season, int $peak, int $step): int
    {
        $month = $peak;

        while (true) {
            $next = (($month - 1 + $step) % 12 + 12) % 12 + 1;

            if (! in_array($next, $season, true) || $next === $peak) {
                return $month;
            }

            $month = $next;
        }
    }
}
