<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\ZoneRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Area::query()->create(['slug' => 'test', 'name' => 'Test', 'name_es' => 'Prueba', 'west' => -71, 'south' => 18, 'east' => -70, 'north' => 19]);
    }

    public function test_a_box_outside_the_areas_can_be_requested_with_a_time_estimate(): void
    {
        $this->post('/zones', ['name' => 'Jamaica', 'email' => 'me@example.com', 'bbox' => [-70.8, 18.2, -70.3, 18.6]])
            ->assertSessionHasErrors('bbox');

        $this->post('/zones', ['name' => 'Jamaica', 'email' => 'me@example.com', 'bbox' => [-78.4, 17.7, -76.2, 18.6]])
            ->assertSessionHas('status', fn (string $s) => str_contains($s, 'minutes') && str_contains($s, 'me@example.com'));

        $this->assertSame(1, ZoneRequest::query()->where('status', 'requested')->count());
    }

    public function test_an_area_is_created_and_an_archive_file_is_imported_in_chunks(): void
    {
        $this->artisan('fire:area', ['slug' => 'jamaica', 'name' => 'Jamaica', 'west' => -78.4, 'south' => 17.7, 'east' => -76.2, 'north' => 18.6, '--name-es' => 'Jamaica'])->assertSuccessful();
        $this->assertDatabaseHas('areas', ['slug' => 'jamaica', 'name_es' => 'Jamaica']);

        $file = tempnam(sys_get_temp_dir(), 'firms');
        $rows = "latitude,longitude,brightness,scan,track,acq_date,acq_time,satellite,instrument,confidence,version,bright_t31,frp,daynight\n";
        for ($i = 0; $i < 7; $i++) {
            $rows .= '18.'.(10 + $i).",-77.3,330.1,1,1,2015-04-2{$i},0512,Terra,MODIS,80,6.1,290,3.2,N\n";
        }
        $rows .= "40.0,10.0,330.1,1,1,2015-04-27,0512,Terra,MODIS,80,6.1,290,3.2,N\n";
        file_put_contents($file, $rows);

        $this->artisan('fire:import', ['files' => [$file], '--chunk' => 3])->expectsOutputToContain('Imported 7 new detections (7 inside the areas)')->assertSuccessful();
        $this->assertSame(7, DB::table('detections')->count());
    }
}
