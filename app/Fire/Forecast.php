<?php

namespace App\Fire;

use App\Models\Area;
use Carbon\CarbonImmutable;

/**
 * Next week's fire for an area, one week ahead, from the harmonized record and NASA POWER weather.
 *
 * Target: burning 1 km cell-days of the coming week (MODIS scale, log1p).
 * Features: the last weeks of fire, the same-week climatology of other years, the last 7 and 28 days of
 * weather, and the time of year. Model: ridge regression, one per area.
 * Validation: rolling origin, one fold per year, against the climatology and persistence baselines.
 */
class Forecast
{
    public const FIRST_FOLD = 2013;

    public const FEATURES = ['fire_last_week', 'fire_2_weeks_ago', 'fire_3_weeks_ago', 'fire_last_4_weeks', 'climatology_same_week', 'temperature_7d', 'humidity_7d', 'wind_7d', 'rain_7d', 'rain_28d', 'temperature_28d', 'humidity_28d', 'season_sin', 'season_cos'];

    public function __construct(private Harmonizer $harmonizer, private Weather $weather) {}

    /** @return array<string, mixed>|null */
    public function run(Area $area): ?array
    {
        return $this->fromSeries(DetectAlerts::weeks($this->harmonizer->daily($area, Sensors::MODIS)), $this->weather->days($area), $this->weather->lastDay($area));
    }

    /**
     * @param  array<string, float>  $weeks  week start → burning cell-days, complete weeks only
     * @param  array<string, array{t2m: ?float, rh2m: ?float, precip: ?float, ws2m: ?float}>  $weather  day → weather
     * @return array<string, mixed>|null
     */
    public function fromSeries(array $weeks, array $weather, ?string $weatherThrough): ?array
    {
        ksort($weeks);
        $starts = array_keys($weeks);
        $values = array_values($weeks);

        if (count($starts) < 200 || $weather === []) {
            return null;
        }

        // Weekly weather, keyed by week start, from the 7 days of that week and the 28 days ending with it.
        $byWeek = [];
        foreach ($starts as $start) {
            $byWeek[$start] = $this->weatherFeatures($weather, CarbonImmutable::parse($start)->addDays(6));
        }

        // One week ahead of the last complete week. When that week has already begun (the record lags a day
        // or two), it is "this week": the outlook people want is the week after, two steps ahead.
        $one = $this->horizon($starts, $values, $byWeek, $weather, $weatherThrough, 1);

        if ($one === null || $one['week_start'] > CarbonImmutable::today()->toDateString()) {
            return $one;
        }

        $two = $this->horizon($starts, $values, $byWeek, $weather, $weatherThrough, 2);

        if ($two === null) {
            return $one;
        }

        $two['current'] = $one;

        return $two;
    }

    /**
     * The model $h weeks ahead: samples, rolling validation and the outlook for the week after the last complete one.
     *
     * @param  list<string>  $starts
     * @param  list<float>  $values
     * @param  array<string, array<string, float>|null>  $byWeek
     * @param  array<string, array{t2m: ?float, rh2m: ?float, precip: ?float, ws2m: ?float}>  $weather
     * @return array<string, mixed>|null
     */
    private function horizon(array $starts, array $values, array $byWeek, array $weather, ?string $weatherThrough, int $h): ?array
    {
        $samples = [];
        foreach ($starts as $i => $start) {
            if ($i < 6 || ! isset($starts[$i + $h]) || $byWeek[$start] === null) {
                continue;
            }
            $next = CarbonImmutable::parse($starts[$i + $h]);
            if ((int) CarbonImmutable::parse($start)->diffInDays($next) !== 7 * $h) {
                continue; // a gap in the record
            }
            $samples[] = ['target_start' => $starts[$i + $h], 'year' => $next->year, 'week' => (int) $next->isoWeek, 'y' => log1p($values[$i + $h]), 'raw' => $values[$i + $h], 'base' => $this->baseFeatures($values, $i, $next), 'weather' => $byWeek[$start], 'persistence' => $values[$i]];
        }

        if (count($samples) < 150) {
            return null;
        }

        // Rolling-origin validation: train on everything before the fold year, test on the fold year.
        $tested = [];
        $lastYear = (int) substr(end($starts), 0, 4);

        for ($fold = self::FIRST_FOLD; $fold <= $lastYear; $fold++) {
            $train = array_values(array_filter($samples, fn ($s) => $s['year'] < $fold));
            $test = array_values(array_filter($samples, fn ($s) => $s['year'] === $fold));

            if (count($train) < 100 || $test === []) {
                continue;
            }

            $clim = $this->climatology($train);
            $model = Ridge::fit(array_map(fn ($s) => $this->row($s, $clim), $train), array_column($train, 'y'));

            foreach ($test as $s) {
                $c = $clim[$s['week']] ?? 0.0;
                $tested[] = ['actual' => $s['raw'], 'model' => max(0.0, expm1($model->predict($this->row($s, $clim)))), 'climatology' => $c, 'persistence' => $s['persistence'], 'log_error' => $model->predict($this->row($s, $clim)) - $s['y']];
            }
        }

        if (count($tested) < 50) {
            return null;
        }

        $mae = fn (string $k) => array_sum(array_map(fn ($t) => abs($t[$k] - $t['actual']), $tested)) / count($tested);
        $validation = [
            'weeks' => count($tested),
            'from' => self::FIRST_FOLD,
            'to' => $lastYear,
            'mae' => round($mae('model'), 1),
            'mae_climatology' => round($mae('climatology'), 1),
            'mae_persistence' => round($mae('persistence'), 1),
            'skill_vs_climatology' => round(1 - $mae('model') / max($mae('climatology'), 1e-9), 3),
            'skill_vs_persistence' => round(1 - $mae('model') / max($mae('persistence'), 1e-9), 3),
            'correlation' => round(Stats::correlation(array_map(fn ($t) => log1p($t['actual']), $tested), array_map(fn ($t) => log1p($t['model']), $tested)), 3),
        ];

        // The final model on every sample, and the outlook for the week after the last complete one.
        $clim = $this->climatology($samples);
        $model = Ridge::fit(array_map(fn ($s) => $this->row($s, $clim), $samples), array_column($samples, 'y'));
        $errors = array_column($tested, 'log_error');
        sort($errors);
        $q = fn (float $p) => $errors[(int) floor($p * (count($errors) - 1))];

        $i = count($starts) - 1;
        $nextStart = CarbonImmutable::parse($starts[$i])->addDays(7 * $h);
        $latestWeather = $this->weatherFeatures($weather, $nextStart->subDay()) ?? $byWeek[$starts[$i]];
        $outlookRow = $this->row(['base' => $this->baseFeatures($values, $i, $nextStart), 'weather' => $latestWeather, 'week' => (int) $nextStart->isoWeek], $clim);
        $pred = $model->predict($outlookRow);
        $c = $clim[(int) $nextStart->isoWeek] ?? 0.0;

        $weights = array_combine(self::FEATURES, array_map(fn ($w) => round($w, 3), $model->standardizedWeights()));
        arsort($weights);

        return [
            'horizon' => $h,
            'week_start' => $nextStart->toDateString(),
            'expected' => round(max(0.0, expm1($pred)), 1),
            'low' => round(max(0.0, expm1($pred - $q(0.8))), 1),
            'high' => round(max(0.0, expm1($pred - $q(0.2))), 1),
            'climatology' => round($c, 1),
            'times_normal' => $c > 0 ? round(expm1($pred) / $c, 1) : null,
            'last_week' => ['start' => $starts[$i], 'value' => round($values[$i], 1)],
            'weather_through' => $weatherThrough,
            'weather' => $weatherThrough ? ['last_7_days' => $this->plainWindow($weather, CarbonImmutable::parse($weatherThrough), 7), 'last_28_days' => $this->plainWindow($weather, CarbonImmutable::parse($weatherThrough), 28)] : null,
            'validation' => $validation,
            'weights' => $weights,
            'samples' => count($samples),
        ];
    }

    /**
     * @param  list<float>  $values
     * @return array<string, float>
     */
    private function baseFeatures(array $values, int $i, CarbonImmutable $next): array
    {
        $angle = 2 * M_PI * ((int) $next->isoWeek) / 52.18;

        return [
            'fire_last_week' => log1p($values[$i]),
            'fire_2_weeks_ago' => log1p($values[$i - 1]),
            'fire_3_weeks_ago' => log1p($values[$i - 2]),
            'fire_last_4_weeks' => log1p(($values[$i] + $values[$i - 1] + $values[$i - 2] + $values[$i - 3]) / 4),
            'season_sin' => sin($angle),
            'season_cos' => cos($angle),
        ];
    }

    /**
     * @param  array<string, array{t2m: ?float, rh2m: ?float, precip: ?float, ws2m: ?float}>  $weather
     * @return array<string, float>|null
     */
    /**
     * The weather as people read it: mean temperature, humidity and wind, total rain, over the window ending at $end.
     *
     * @param  array<string, array{t2m: ?float, rh2m: ?float, precip: ?float, ws2m: ?float}>  $weather
     * @return array{temperature: ?float, humidity: ?float, rain: ?float, wind: ?float, days: int}|null
     */
    private function plainWindow(array $weather, CarbonImmutable $end, int $days): ?array
    {
        $acc = ['t2m' => [], 'rh2m' => [], 'precip' => [], 'ws2m' => []];
        for ($d = 0; $d < $days; $d++) {
            $w = $weather[$end->subDays($d)->toDateString()] ?? null;
            if ($w === null) {
                continue;
            }
            foreach ($acc as $k => $_) {
                if ($w[$k] !== null) {
                    $acc[$k][] = $w[$k];
                }
            }
        }
        if ($acc['t2m'] === []) {
            return null;
        }
        $mean = fn (array $v) => $v === [] ? null : round(array_sum($v) / count($v), 1);

        return ['temperature' => $mean($acc['t2m']), 'humidity' => $mean($acc['rh2m']), 'rain' => $acc['precip'] === [] ? null : round(array_sum($acc['precip']) * $days / count($acc['precip']), 1), 'wind' => $mean($acc['ws2m']), 'days' => count($acc['t2m'])];
    }

    private function weatherFeatures(array $weather, CarbonImmutable $end): ?array
    {
        $take = function (int $days) use ($weather, $end): ?array {
            $acc = ['t2m' => [], 'rh2m' => [], 'ws2m' => [], 'precip' => []];
            for ($d = 0; $d < $days; $d++) {
                $w = $weather[$end->subDays($d)->toDateString()] ?? null;
                if ($w === null) {
                    continue;
                }
                foreach ($acc as $k => $_) {
                    if ($w[$k] !== null) {
                        $acc[$k][] = $w[$k];
                    }
                }
            }
            // Accept the window when at least half of its days exist.
            if (count($acc['t2m']) < $days / 2) {
                return null;
            }

            return ['t2m' => array_sum($acc['t2m']) / count($acc['t2m']), 'rh2m' => array_sum($acc['rh2m']) / max(count($acc['rh2m']), 1), 'ws2m' => array_sum($acc['ws2m']) / max(count($acc['ws2m']), 1), 'precip' => array_sum($acc['precip']) * $days / max(count($acc['precip']), 1)];
        };

        $w7 = $take(7);
        $w28 = $take(28);

        if ($w7 === null || $w28 === null) {
            return null;
        }

        return ['temperature_7d' => $w7['t2m'], 'humidity_7d' => $w7['rh2m'], 'wind_7d' => $w7['ws2m'], 'rain_7d' => log1p($w7['precip']), 'rain_28d' => log1p($w28['precip']), 'temperature_28d' => $w28['t2m'], 'humidity_28d' => $w28['rh2m']];
    }

    /**
     * Median of the target by ISO week over the given samples: the climatology a forecaster would know.
     *
     * @param  list<array<string, mixed>>  $samples
     * @return array<int, float>
     */
    private function climatology(array $samples): array
    {
        $byWeek = [];
        foreach ($samples as $s) {
            $byWeek[$s['week']][] = $s['raw'];
        }

        return array_map(fn ($v) => Stats::median($v), $byWeek);
    }

    /**
     * @param  array<string, mixed>  $s
     * @param  array<int, float>  $clim
     * @return list<float>
     */
    private function row(array $s, array $clim): array
    {
        $all = $s['base'] + $s['weather'] + ['climatology_same_week' => log1p($clim[$s['week']] ?? 0.0)];

        return array_map(fn ($name) => (float) $all[$name], self::FEATURES);
    }
}
