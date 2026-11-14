<?php

namespace App\Fire;

class Stats
{
    public static function median(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    /** Median absolute deviation, scaled to match a standard deviation for normal data. */
    public static function mad(array $values): float
    {
        $median = self::median($values);

        return 1.4826 * self::median(array_map(fn ($v) => abs($v - $median), $values));
    }

    public static function correlation(array $x, array $y): float
    {
        $n = count($x);

        if ($n < 2 || $n !== count($y)) {
            return 0.0;
        }

        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $sxy = $sxx = $syy = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $mx;
            $dy = $y[$i] - $my;
            $sxy += $dx * $dy;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
        }

        return $sxx > 0 && $syy > 0 ? $sxy / sqrt($sxx * $syy) : 0.0;
    }

    public static function meanAbsoluteError(array $predicted, array $observed): float
    {
        $n = count($observed);

        if ($n === 0) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($observed as $i => $value) {
            $sum += abs($predicted[$i] - $value);
        }

        return $sum / $n;
    }

    /**
     * Σ numerator ÷ Σ denominator with a 95% interval from resampling the days.
     *
     * @return array{ratio: float, low: float, high: float, days: int}
     */
    public static function ratio(array $numerators, array $denominators, int $resamples = 200, int $seed = 7): array
    {
        $n = count($numerators);
        $point = self::divide(array_sum($numerators), array_sum($denominators));

        if ($n < 3) {
            return ['ratio' => $point, 'low' => $point, 'high' => $point, 'days' => $n];
        }

        mt_srand($seed);
        $draws = [];

        for ($r = 0; $r < $resamples; $r++) {
            $top = $bottom = 0;

            for ($i = 0; $i < $n; $i++) {
                $pick = mt_rand(0, $n - 1);
                $top += $numerators[$pick];
                $bottom += $denominators[$pick];
            }

            $draws[] = self::divide($top, $bottom);
        }

        sort($draws);

        return [
            'ratio' => $point,
            'low' => $draws[(int) floor(0.025 * ($resamples - 1))],
            'high' => $draws[(int) ceil(0.975 * ($resamples - 1))],
            'days' => $n,
        ];
    }

    private static function divide(float $top, float $bottom): float
    {
        return $bottom > 0 ? $top / $bottom : 0.0;
    }
}
