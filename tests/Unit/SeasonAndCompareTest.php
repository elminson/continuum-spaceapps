<?php

namespace Tests\Unit;

use App\Fire\CompareDates;
use App\Fire\FireSeason;
use PHPUnit\Framework\TestCase;

class SeasonAndCompareTest extends TestCase
{
    /** Hispaniola-like: burning February to April, peak in March. */
    private function months(int $years = 5): array
    {
        $shape = [1 => 20, 2 => 60, 3 => 100, 4 => 70, 5 => 30, 6 => 10, 7 => 10, 8 => 10, 9 => 5, 10 => 5, 11 => 5, 12 => 10];
        $out = [];

        for ($y = 2020; $y < 2020 + $years; $y++) {
            foreach ($shape as $m => $v) {
                $out[] = ['month' => sprintf('%d-%02d', $y, $m), 'value' => (float) $v];
            }
        }

        return $out;
    }

    public function test_finds_the_season_around_the_peak(): void
    {
        $season = FireSeason::from($this->months(), '2026-03-10');

        $this->assertSame(3, $season['peak']);
        $this->assertSame(2, $season['start']);
        $this->assertSame(4, $season['end']);
        $this->assertSame([3, 2, 4], $season['season']);
        $this->assertTrue($season['in_season']);
        $this->assertSame(5, $season['years']);
        $this->assertFalse(FireSeason::from($this->months(), '2026-09-01')['in_season']);
    }

    public function test_a_season_can_cross_new_year(): void
    {
        $months = array_map(fn ($m) => ['month' => $m['month'], 'value' => in_array((int) substr($m['month'], 5), [12, 1], true) ? 100.0 : ((int) substr($m['month'], 5) === 2 ? 60.0 : 5.0)], $this->months());

        $season = FireSeason::from($months, '2026-01-15');

        $this->assertSame(12, $season['start']);
        $this->assertSame(2, $season['end']);
        $this->assertTrue($season['in_season']);
    }

    public function test_needs_three_complete_years(): void
    {
        $this->assertNull(FireSeason::from($this->months(2)));
    }

    public function test_compares_a_range_with_the_same_dates_in_other_years(): void
    {
        $daily = [];

        foreach (range(2018, 2022) as $year) {
            foreach (['03-01', '03-02', '03-03'] as $md) {
                $daily[] = ['date' => "{$year}-{$md}", 'value' => $year === 2022 ? 30.0 : 10.0];
            }
        }

        $result = CompareDates::run($daily, '2022-03-01', '2022-03-03');

        $this->assertSame(90.0, $result['value']);
        $this->assertSame(30.0, $result['typical']);
        $this->assertSame(3.0, $result['times']);
        $this->assertSame(1, $result['rank']);
        $this->assertSame(5, $result['of']);
        $this->assertSame(3, $result['days']);
    }
}
