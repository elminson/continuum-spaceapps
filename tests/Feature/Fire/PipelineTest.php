<?php

namespace Tests\Feature\Fire;

use App\Fire\Explorer;
use App\Fire\FirmsFeed;
use App\Fire\Harmonizer;
use App\Fire\Pipeline;
use App\Fire\RetirementTest;
use App\Models\Alert;
use App\Models\Area;
use App\Models\AreaFit;
use App\Models\IngestRun;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->area = Area::query()->create(['slug' => 'test', 'name' => 'Test', 'name_es' => 'Prueba', 'west' => -71, 'south' => 18, 'east' => -70, 'north' => 19]);
    }

    /** Per fire and day: one MODIS pixel, and VIIRS pixels in four ~1 km cells (two pixels each). */
    private function seedFires(string $from, string $to, ?callable $fires = null): void
    {
        $rows = [];
        $fires ??= fn (CarbonImmutable $d) => 2 + $d->dayOfYear % 5;

        for ($d = CarbonImmutable::parse($from); $d->lte($to); $d = $d->addDay()) {
            for ($f = 0; $f < $fires($d); $f++) {
                $lat = 18.205;
                $lon = -70.955 + $f * 0.02;
                $rows[] = $this->pixel('modis', 'A', $d, $lat, $lon);

                if ($d->toDateString() >= '2012-01-20') {
                    // VIIRS: the same fire in 4 cells, each seen by two 375 m pixels.
                    foreach ([0, 0.01, 0.02, 0.03] as $offset) {
                        $rows[] = $this->pixel('viirs', 'N20', $d, $lat + $offset, $lon, 0);
                        $rows[] = $this->pixel('viirs', 'N20', $d, $lat + $offset + 0.001, $lon, 1);
                    }
                }
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('detections')->insert($chunk);
        }
    }

    private function pixel(string $instrument, string $satellite, CarbonImmutable $day, float $lat, float $lon, int $minute = 0): array
    {
        return ['instrument' => $instrument, 'satellite' => $satellite, 'acquired_at' => $day->toDateString()." 13:0{$minute}:00", 'acq_date' => $day->toDateString(), 'latitude' => $lat, 'longitude' => $lon, 'frp' => 10, 'confidence' => 'n', 'daynight' => 'D', 'version' => '2.0'];
    }

    public function test_collapses_pixels_into_cells_and_fits_one_modis_cell_per_four_viirs_cells(): void
    {
        $this->seedFires('2014-01-01', '2014-03-31');

        app(Pipeline::class)->run('2014-01-01', '2014-03-31');

        $day = DB::table('area_days')->where('area_id', $this->area->id)->where('acq_date', '2014-01-01')->pluck('cell_days', 'instrument');
        $this->assertSame(['modis' => 3, 'viirs' => 12], $day->sortKeys()->all());

        $fit = AreaFit::query()->where('area_id', $this->area->id)->where('month', 0)->sole();
        $this->assertEqualsWithDelta(0.25, $fit->ratio, 1e-9);
        $this->assertEquals(1, $fit->validation['improvement']);
        $this->assertNotNull($this->area->fresh()->processed_at);
    }

    public function test_puts_years_before_viirs_on_the_viirs_scale(): void
    {
        $this->seedFires('2011-12-01', '2012-04-30');

        app(Pipeline::class)->run('2011-12-01', '2012-04-30');

        $months = collect(app(Harmonizer::class)->monthly($this->area->fresh(), 'viirs'))->keyBy('month');

        $this->assertTrue($months['2011-12']['estimated']);
        $this->assertNull($months['2011-12']['viirs']);
        $this->assertEqualsWithDelta($months['2011-12']['modis'] * 4, $months['2011-12']['value'], 1e-6);
        $this->assertFalse($months['2012-03']['estimated']);
    }

    public function test_flags_a_week_far_above_the_same_week_in_other_years(): void
    {
        $this->seedFires('2013-01-01', '2020-12-31', fn (CarbonImmutable $d) => $d->year === 2019 && $d->isoWeek === 30 ? 40 : 2);

        app(Pipeline::class)->run('2013-01-01', '2020-12-31');

        $alert = Alert::query()->where('area_id', $this->area->id)->orderByDesc('score')->first();
        $this->assertNotNull($alert);
        $this->assertSame('2019-07-22', $alert->week_start->toDateString());
        $this->assertSame(280.0, $alert->observed);
        $this->assertSame(14.0, $alert->typical);
        $this->assertSame('extreme', $alert->severity);
    }

    public function test_simulates_modis_retirement_from_earlier_years_only(): void
    {
        $this->seedFires('2014-01-01', '2016-12-31');
        app(Pipeline::class)->run('2014-01-01', '2016-12-31');

        $result = app(RetirementTest::class)->run($this->area->fresh(), 2016);

        $this->assertTrue($result['available']);
        $this->assertSame([2015, 2016], $result['years']);
        $this->assertSame(2016, $result['year']);
        $this->assertSame(0.0, (float) $result['total_error']);
        $this->assertSame(1.0, (float) $result['improvement']);
        $this->assertSame(366, $result['test_days']);
    }

    public function test_explores_a_smaller_box_inside_an_area_with_its_own_fit(): void
    {
        $this->seedFires('2014-01-01', '2015-12-31');
        app(Pipeline::class)->run('2014-01-01', '2015-12-31');

        $result = app(Explorer::class)->analyze([-71.0, 18.0, -70.5, 18.5]);

        $this->assertTrue($result['available']);
        $this->assertEqualsWithDelta(0.25, $result['fit']['ratio'], 1e-9);
        $this->assertEquals(1, $result['fit']['validation']['improvement']);
        $this->assertCount(24, $result['monthly']);
        $this->assertNull(Explorer::reject([-71.0, 18.0, -70.5, 18.5]));
        $this->assertSame('Draw the box inside one of the dashed areas.', Explorer::reject([-72.0, 18.0, -70.5, 18.5]));
    }

    public function test_imports_firms_csv_rows_inside_an_area_once(): void
    {
        $csv = "latitude,longitude,bright_ti4,scan,track,acq_date,acq_time,satellite,instrument,confidence,version,bright_ti5,frp,daynight\n"
            ."18.5,-70.5,330.1,0.4,0.4,2026-11-14,0512,N21,VIIRS,n,2.0NRT,290,3.2,N\n"
            ."40.0,10.0,330.1,0.4,0.4,2026-11-14,0512,N21,VIIRS,n,2.0NRT,290,3.2,N\n";

        $first = app(FirmsFeed::class)->import($csv);
        $again = app(FirmsFeed::class)->import($csv);

        $this->assertSame(['read' => 2, 'kept' => 1, 'inserted' => 1, 'from' => '2026-11-14', 'to' => '2026-11-14'], $first);
        $this->assertSame(0, $again['inserted']);
        $this->assertSame('viirs', DB::table('detections')->value('instrument'));
        $this->assertSame('2026-11-14 05:12:00', DB::table('detections')->value('acquired_at'));
    }

    public function test_records_every_feed_pull_as_an_ingest_run(): void
    {
        $outside = "latitude,longitude,brightness,scan,track,acq_date,acq_time,satellite,instrument,confidence,version,bright_t31,frp,daynight\n"
            ."40.0,10.0,330.1,1,1,2026-11-14,0512,Terra,MODIS,80,6.1NRT,290,3.2,N\n";
        Http::fake([
            FirmsFeed::FEEDS['modis'] => Http::response($outside),
            FirmsFeed::FEEDS['noaa20'] => Http::response('down', 503),
            FirmsFeed::FEEDS['noaa21'] => Http::response($outside),
        ]);

        $this->artisan('fire:ingest')->assertSuccessful();

        $this->assertSame(['done', 'failed', 'done'], IngestRun::query()->orderBy('id')->pluck('status')->all());
        $this->assertSame(1, IngestRun::query()->where('feed', 'modis')->value('read'));
        $this->assertStringContainsString('503', (string) IngestRun::query()->where('feed', 'noaa20')->value('error'));

        $this->get('/data?status=failed')->assertInertia(fn ($page) => $page->component('Data')->where('total', 1)->where('runs.0.feed', 'noaa20'));
    }
}
