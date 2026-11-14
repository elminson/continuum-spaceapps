<?php

namespace App\Fire;

use App\Models\Area;
use App\Models\AreaFit;
use Illuminate\Support\Facades\DB;

/**
 * Fits MODIS ÷ VIIRS cell-days per calendar month (and all-year, month 0)
 * on the days both instruments flew, and scores it honestly: fitted on
 * three of every four overlap days, tested on the fourth.
 */
class Calibrate
{
    public const HOLDOUT_EVERY = 4;

    public const MIN_MONTH_DAYS = 20;

    public function __construct(private DailyRecord $record) {}

    /** @return array<string, mixed> validation summary */
    public function __invoke(Area $area): array
    {
        $pairs = self::overlap($this->record->for($area));

        if (count($pairs) < 2 * self::HOLDOUT_EVERY) {
            return ['status' => 'not enough overlap', 'days' => count($pairs)];
        }

        $validation = self::validate($pairs);

        $final = self::fit($pairs);

        DB::transaction(function () use ($area, $final, $validation): void {
            AreaFit::query()->where('area_id', $area->id)->delete();

            foreach ($final as $month => $fit) {
                AreaFit::query()->create([
                    'area_id' => $area->id,
                    'month' => $month,
                    'ratio' => $fit['ratio'],
                    'ratio_low' => $fit['low'],
                    'ratio_high' => $fit['high'],
                    'days' => $fit['days'],
                    'validation' => $month === 0 ? $validation : null,
                ]);
            }
        });

        return $validation;
    }

    /**
     * Fit on three of every four overlap days, score on the fourth.
     *
     * @param  list<array{date: string, month: int, modis: int, viirs: int}>  $pairs
     * @return array<string, mixed>
     */
    public static function validate(array $pairs): array
    {
        $train = $test = [];

        foreach ($pairs as $i => $pair) {
            (($i + 1) % self::HOLDOUT_EVERY === 0) ? $test[] = $pair : $train[] = $pair;
        }

        $trainFit = self::fit($train);
        $predicted = array_map(fn (array $p) => $p['viirs'] * self::factor($trainFit, $p['month'])['ratio'], $test);
        $observed = array_column($test, 'modis');
        $mae = Stats::meanAbsoluteError($predicted, $observed);
        $naiveMae = Stats::meanAbsoluteError(array_column($test, 'viirs'), $observed);

        return [
            'train_days' => count($train),
            'test_days' => count($test),
            'mae' => round($mae, 2),
            'naive_mae' => round($naiveMae, 2),
            'improvement' => $naiveMae > 0 ? round(1 - $mae / $naiveMae, 4) : 0.0,
            'correlation' => round(Stats::correlation($predicted, $observed), 4),
            'overlap_from' => $pairs[0]['date'],
            'overlap_to' => $pairs[count($pairs) - 1]['date'],
        ];
    }

    /**
     * @param  array<string, array{modis: ?int, viirs: ?int}>  $days
     * @return list<array{date: string, month: int, modis: int, viirs: int}>
     */
    public static function overlap(array $days): array
    {
        $pairs = [];

        foreach ($days as $date => $day) {
            if ($date >= Sensors::VIIRS_START && $day['modis'] !== null && $day['viirs'] !== null) {
                $pairs[] = ['date' => $date, 'month' => (int) substr($date, 5, 2), 'modis' => $day['modis'], 'viirs' => $day['viirs']];
            }
        }

        return $pairs;
    }

    /**
     * @param  list<array{date: string, month: int, modis: int, viirs: int}>  $pairs
     * @return array<int, array{ratio: float, low: float, high: float, days: int}>
     */
    public static function fit(array $pairs, int $resamples = 200): array
    {
        $fits = [0 => Stats::ratio(array_column($pairs, 'modis'), array_column($pairs, 'viirs'), $resamples)];
        $byMonth = [];

        foreach ($pairs as $pair) {
            $byMonth[$pair['month']][] = $pair;
        }

        foreach ($byMonth as $month => $subset) {
            if (count($subset) >= self::MIN_MONTH_DAYS) {
                $fits[$month] = Stats::ratio(array_column($subset, 'modis'), array_column($subset, 'viirs'), $resamples);
            }
        }

        ksort($fits);

        return $fits;
    }

    /**
     * @param  array<int, array{ratio: float, low: float, high: float, days: int}>  $fits
     * @return array{ratio: float, low: float, high: float, days: int}
     */
    public static function factor(array $fits, int $month): array
    {
        return $fits[$month] ?? $fits[0];
    }
}
