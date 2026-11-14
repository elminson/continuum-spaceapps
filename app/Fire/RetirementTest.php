<?php

namespace App\Fire;

use App\Models\Area;

/**
 * What happens when MODIS stops: pretend it ended on 1 January of a chosen
 * year, fit the ratio on the earlier overlap only, rebuild every later
 * month from VIIRS alone and compare it with the MODIS record that exists.
 */
class RetirementTest
{
    public function __construct(private DailyRecord $record) {}

    /** @return array<string, mixed> */
    public function run(Area $area, ?int $year = null): array
    {
        $pairs = Calibrate::overlap($this->record->for($area));
        $years = $this->years($pairs);

        if ($years === []) {
            return ['available' => false, 'years' => []];
        }

        $year = in_array($year, $years, true) ? $year : $years[max(0, count($years) - 3)];
        $cutoff = "{$year}-01-01";
        $before = array_values(array_filter($pairs, fn ($p) => $p['date'] < $cutoff));
        $after = array_values(array_filter($pairs, fn ($p) => $p['date'] >= $cutoff));
        $fits = Calibrate::fit($before, 100);

        $months = [];
        $predicted = [];

        foreach ($after as $p) {
            $f = Calibrate::factor($fits, $p['month']);
            $predicted[] = $p['viirs'] * $f['ratio'];
            $key = substr($p['date'], 0, 7);
            $months[$key] ??= ['month' => $key, 'modis' => 0, 'estimate' => 0.0, 'low' => 0.0, 'high' => 0.0];
            $months[$key]['modis'] += $p['modis'];
            $months[$key]['estimate'] += $p['viirs'] * $f['ratio'];
            $months[$key]['low'] += $p['viirs'] * $f['low'];
            $months[$key]['high'] += $p['viirs'] * $f['high'];
        }

        $observed = array_column($after, 'modis');
        $mae = Stats::meanAbsoluteError($predicted, $observed);
        $naiveMae = Stats::meanAbsoluteError(array_column($after, 'viirs'), $observed);
        $actual = array_sum($observed);
        $estimated = array_sum($predicted);

        $context = [];

        foreach ($before as $p) {
            if ($p['date'] >= ($year - 3).'-01-01') {
                $key = substr($p['date'], 0, 7);
                $context[$key] ??= ['month' => $key, 'modis' => 0, 'estimate' => null, 'low' => null, 'high' => null];
                $context[$key]['modis'] += $p['modis'];
            }
        }

        return [
            'available' => true,
            'years' => $years,
            'year' => $year,
            'train_days' => count($before),
            'test_days' => count($after),
            'total_error' => $actual > 0 ? round(($estimated - $actual) / $actual, 4) : null,
            'improvement' => $naiveMae > 0 ? round(1 - $mae / $naiveMae, 4) : 0.0,
            'monthly_r' => round(Stats::correlation(array_column($months, 'estimate'), array_column($months, 'modis')), 4),
            'actual_total' => $actual,
            'estimated_total' => round($estimated),
            'months' => array_map(function (array $m) {
                foreach (['estimate', 'low', 'high'] as $f) {
                    $m[$f] = $m[$f] === null ? null : round($m[$f], 1);
                }

                return $m;
            }, [...array_values($context), ...array_values($months)]),
        ];
    }

    /**
     * Cutoff years with at least a year of overlap before and half a year after.
     *
     * @param  list<array{date: string, month: int, modis: int, viirs: int}>  $pairs
     * @return list<int>
     */
    private function years(array $pairs): array
    {
        if ($pairs === []) {
            return [];
        }

        $years = [];

        for ($y = (int) substr($pairs[0]['date'], 0, 4) + 1; $y <= (int) substr(end($pairs)['date'], 0, 4); $y++) {
            $before = count(array_filter($pairs, fn ($p) => $p['date'] < "{$y}-01-01"));

            if ($before >= 365 && count($pairs) - $before >= 180) {
                $years[] = $y;
            }
        }

        return $years;
    }
}
