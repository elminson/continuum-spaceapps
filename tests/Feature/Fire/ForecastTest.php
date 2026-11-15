<?php

namespace Tests\Feature\Fire;

use App\Fire\Forecast;
use App\Fire\Ridge;
use App\Fire\Weather;
use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_ridge_recovers_a_linear_relation(): void
    {
        mt_srand(3);
        $x = [];
        $y = [];
        for ($i = 0; $i < 400; $i++) {
            $a = mt_rand(0, 1000) / 100;
            $b = mt_rand(0, 1000) / 100;
            $x[] = [$a, $b];
            $y[] = 2.0 + 3.0 * $a - 1.5 * $b + (mt_rand(-100, 100) / 1000);
        }

        $model = Ridge::fit($x, $y, 0.01);

        $this->assertEqualsWithDelta(2.0 + 3.0 * 4 - 1.5 * 2, $model->predict([4.0, 2.0]), 0.1);
        $this->assertGreaterThan(0, $model->standardizedWeights()[0]);
        $this->assertLessThan(0, $model->standardizedWeights()[1]);
    }

    public function test_power_responses_are_stored_without_fill_values(): void
    {
        $area = Area::query()->create(['slug' => 'test', 'name' => 'Test', 'name_es' => 'Prueba', 'west' => -71, 'south' => 18, 'east' => -70, 'north' => 19]);
        $stored = app(Weather::class)->store($area, [
            'header' => ['fill_value' => -999.0],
            'properties' => ['parameter' => [
                'T2M' => ['20261101' => 27.1, '20261102' => -999.0],
                'RH2M' => ['20261101' => 80.5, '20261102' => -999.0],
                'PRECTOTCORR' => ['20261101' => 3.2, '20261102' => -999.0],
                'WS2M' => ['20261101' => 2.4, '20261102' => -999.0],
            ]],
        ]);

        $this->assertSame(1, $stored);
        $this->assertSame('2026-11-01', app(Weather::class)->lastDay($area));
        $this->assertEquals(27.1, DB::table('weather_days')->value('t2m'));
    }

    public function test_the_forecast_beats_climatology_when_weather_drives_the_fire(): void
    {
        // Twenty years of weekly fire: a seasonal cycle, plus a dry-spell effect that only the weather can explain.
        mt_srand(11);
        $weeks = [];
        $weather = [];
        $start = CarbonImmutable::parse('2003-01-06');
        $dry = 0.0;

        for ($w = 0; $w < 20 * 52; $w++) {
            $monday = $start->addWeeks($w);
            $season = 1 + cos(2 * M_PI * ($monday->dayOfYear - 80) / 365.25);
            $dry = 0.8 * $dry + (mt_rand(0, 100) / 100 - 0.5);
            $rain = max(0.0, 3 - 3 * $dry + mt_rand(-50, 50) / 100);
            for ($d = 0; $d < 7; $d++) {
                $weather[$monday->addDays($d)->toDateString()] = ['t2m' => 25 + 3 * $dry, 'rh2m' => 75 - 15 * $dry, 'precip' => $rain, 'ws2m' => 2.0];
            }
            $weeks[$monday->toDateString()] = max(0.0, 20 * $season * (1 + 1.5 * $dry) + mt_rand(-300, 300) / 100);
        }

        $f = app(Forecast::class)->fromSeries($weeks, $weather, array_key_last($weather));

        $this->assertNotNull($f);
        // The series ends in 2022, so one week ahead is long past: it becomes "this week" and the outlook is two weeks ahead.
        $this->assertSame($start->addWeeks(20 * 52)->toDateString(), $f['current']['week_start']);
        $this->assertSame(1, $f['current']['horizon']);
        $this->assertSame($start->addWeeks(20 * 52 + 1)->toDateString(), $f['week_start']);
        $this->assertSame(2, $f['horizon']);
        $this->assertGreaterThan(0.1, $f['current']['validation']['skill_vs_climatology']);
        $this->assertGreaterThan(0.5, $f['current']['validation']['correlation']);
        $this->assertGreaterThan(0, $f['validation']['skill_vs_climatology']);
        $this->assertGreaterThanOrEqual($f['expected'], $f['high']);
        $this->assertGreaterThanOrEqual(0, $f['low']);
        $this->assertCount(count(Forecast::FEATURES), $f['weights']);
    }
}
