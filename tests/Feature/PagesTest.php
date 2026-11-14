<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Area;
use App\Models\AreaFit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->area = Area::query()->create(['slug' => 'cuba', 'name' => 'Cuba', 'name_es' => 'Cuba', 'west' => -84.6, 'south' => 18.7, 'east' => -74.2, 'north' => 24.9, 'processed_at' => now()]);
        AreaFit::query()->create(['area_id' => $this->area->id, 'month' => 0, 'ratio' => 0.24, 'ratio_low' => 0.23, 'ratio_high' => 0.25, 'days' => 5000, 'validation' => ['train_days' => 3750, 'test_days' => 1250, 'mae' => 8.0, 'naive_mae' => 58.0, 'improvement' => 0.86, 'correlation' => 0.85, 'overlap_from' => '2012-01-20', 'overlap_to' => '2026-11-14']]);
        Alert::query()->create(['area_id' => $this->area->id, 'week_start' => '2026-08-17', 'observed' => 321, 'typical' => 15, 'spread' => 15.6, 'score' => 19.6, 'severity' => 'extreme']);
    }

    public function test_home_lists_the_areas_and_the_most_unusual_weeks(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('areas.0.slug', 'cuba')
            ->where('areas.0.improvement', 0.86)
            ->where('topAlerts.0.times', 21.4)
            ->where('locale', 'en'));
    }

    public function test_area_page_on_either_scale(): void
    {
        $this->get('/areas/cuba?scale=viirs')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Area')
            ->where('scale', 'viirs')
            ->where('fit.ratio', 0.24)
            ->where('alerts.0.week_start', '2026-08-17'));
    }

    public function test_method_page_and_spanish(): void
    {
        $this->get('/method')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Method'));

        $this->get('/locale/es')->assertRedirect()->assertCookie('locale', 'es', false);
        $this->withUnencryptedCookie('locale', 'es')->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'es')
            ->where('translations.Areas', 'Áreas'));
    }

    public function test_public_api_and_csv(): void
    {
        $this->getJson('/api/areas')->assertOk()->assertJsonPath('data.0.slug', 'cuba');
        $this->getJson('/api/areas/cuba/monthly')->assertOk()->assertJsonPath('scale', 'modis')->assertJsonPath('fit.ratio', 0.24);
        $this->getJson('/api/areas/cuba/alerts')->assertOk()->assertJsonPath('data.0.severity', 'extreme');
        $this->getJson('/api/areas/cuba/retirement')->assertOk()->assertJsonPath('data.available', false);
        $this->get('/api/areas/cuba/monthly.csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
