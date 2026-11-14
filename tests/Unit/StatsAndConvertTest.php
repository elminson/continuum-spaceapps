<?php

namespace Tests\Unit;

use App\Fire\Harmonizer;
use App\Fire\Sensors;
use App\Fire\Stats;
use PHPUnit\Framework\TestCase;

class StatsAndConvertTest extends TestCase
{
    public function test_ratio_of_sums_with_an_interval_around_it(): void
    {
        $fit = Stats::ratio([1, 2, 3, 4, 5], [4, 8, 12, 16, 20]);

        $this->assertSame(0.25, $fit['ratio']);
        $this->assertSame(0.25, $fit['low']);
        $this->assertSame(0.25, $fit['high']);
        $this->assertSame(5, $fit['days']);
    }

    public function test_median_and_mad(): void
    {
        $this->assertSame(3.0, Stats::median([5, 1, 3]));
        $this->assertSame(2.5, Stats::median([1, 2, 3, 4]));
        $this->assertEqualsWithDelta(1.4826, Stats::mad([1, 2, 3, 4, 5]), 1e-9);
    }

    public function test_uses_the_chosen_sensor_when_it_flew_and_converts_the_other_otherwise(): void
    {
        $fits = [0 => ['ratio' => 0.25, 'low' => 0.2, 'high' => 0.3, 'days' => 100]];

        $own = Harmonizer::convert(['modis' => 10, 'viirs' => 40], $fits, 3, Sensors::MODIS);
        $this->assertSame(['value' => 10.0, 'low' => 10.0, 'high' => 10.0, 'estimated' => false], $own);

        $fromViirs = Harmonizer::convert(['modis' => null, 'viirs' => 40], $fits, 3, Sensors::MODIS);
        $this->assertSame(['value' => 10.0, 'low' => 8.0, 'high' => 12.0, 'estimated' => true], $fromViirs);

        $fromModis = Harmonizer::convert(['modis' => 10, 'viirs' => null], $fits, 3, Sensors::VIIRS);
        $this->assertEqualsWithDelta(40.0, $fromModis['value'], 1e-9);
        $this->assertTrue($fromModis['estimated']);
        $this->assertLessThan($fromModis['high'], $fromModis['low']);

        $this->assertNull(Harmonizer::convert(['modis' => null, 'viirs' => null], $fits, 3, Sensors::MODIS)['value']);
    }
}
