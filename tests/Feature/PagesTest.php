<?php

namespace Tests\Feature;

use App\Fire\Explorer;
use App\Models\Alert;
use App\Models\Area;
use App\Models\AreaFit;
use App\Models\User;
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

    public function test_menu_pages_render_without_login(): void
    {
        foreach (['/areas' => 'Areas', '/data' => 'Data', '/how-it-works' => 'HowItWorks', '/api-docs' => 'ApiDocs', '/login' => 'auth/Login'] as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('places.0.slug', 'dominican-republic')->has('status.detections'));
    }

    public function test_signed_in_users_save_and_delete_their_boxes(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->post('/boxes', ['name' => 'Valle Nuevo', 'bbox' => [-70.8, 18.7, -70.5, 18.9]])->assertRedirect('/login');

        $this->actingAs($user)->post('/boxes', ['name' => 'Valle Nuevo', 'bbox' => [-70.8, 18.7, -70.5, 18.9]])->assertRedirect();
        $this->actingAs($user)->post('/boxes', ['name' => 'Madrid', 'bbox' => [-4, 40, -3, 41]])->assertSessionHasErrors('bbox');

        $box = $user->savedBoxes()->sole();
        $this->actingAs($user)->get('/explore')->assertInertia(fn (Assert $page) => $page->where('boxes.0.name', 'Valle Nuevo')->where('boxes.0.bbox', [-70.8, 18.7, -70.5, 18.9]));

        $this->actingAs(User::factory()->create())->delete("/boxes/{$box->id}")->assertForbidden();
        $this->actingAs($user)->delete("/boxes/{$box->id}")->assertRedirect();
        $this->assertSame(0, $user->savedBoxes()->count());
    }

    public function test_unusual_periods_filter_by_area_and_severity(): void
    {
        $this->get('/alerts?area=cuba&severity=extreme&sort=strongest')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Alerts')
            ->where('filters', ['area' => 'cuba', 'severity' => 'extreme', 'sort' => 'strongest'])
            ->where('total', 1)
            ->where('alerts.0.href', '/areas/cuba?week=2026-08-17')
            ->where('sources.0.counts.extreme', 1));

        $this->get('/alerts?area=cuba&severity=high')->assertInertia(fn (Assert $page) => $page->where('total', 0));
        $this->get('/alerts?area=nowhere')->assertInertia(fn (Assert $page) => $page->where('filters.area', ''));
        $this->get('/alerts?area=dominican-republic')->assertOk()->assertInertia(fn (Assert $page) => $page->where('filters.area', 'dominican-republic'));
    }

    public function test_every_ready_made_place_fits_inside_an_area(): void
    {
        $this->seed();

        foreach (Explorer::PLACES as $slug => [$name, $nameEs, $bbox]) {
            $this->assertNull(Explorer::reject($bbox), $slug);
        }
    }

    public function test_public_api_and_csv(): void
    {
        $this->getJson('/api/areas')->assertOk()->assertJsonPath('data.0.slug', 'cuba');
        $this->getJson('/api/areas/cuba/monthly')->assertOk()->assertJsonPath('scale', 'modis')->assertJsonPath('fit.ratio', 0.24);
        $this->getJson('/api/areas/cuba/alerts')->assertOk()->assertJsonPath('data.0.severity', 'extreme');
        $this->getJson('/api/week?bbox=-80,20,-79,21&week=2026-08-19')->assertOk()->assertJsonPath('week', '2026-08-17');
        $this->getJson('/api/week?bbox=-80,20,-79,21&days=30')->assertOk()->assertJsonPath('to', now()->toDateString())->assertJsonPath('from', now()->subDays(29)->toDateString());
        $this->getJson('/api/explore?bbox=-80,20,-79,21')->assertOk()->assertJsonPath('data.available', false);
        $this->getJson('/api/explore?bbox=10,10,11,11')->assertStatus(422)->assertJsonPath('message', 'Draw the box inside one of the four areas.');
        $this->get('/explore')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Explore')->where('areas.0.slug', 'cuba')->where('places.0.slug', 'dominican-republic'));
        $this->getJson('/api/areas/cuba/week?week=2026-08-19')->assertOk()->assertJsonPath('week', '2026-08-17')->assertJsonPath('type', 'FeatureCollection');
        $this->getJson('/api/areas/cuba/retirement')->assertOk()->assertJsonPath('data.available', false);
        $this->get('/api/areas/cuba/monthly.csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
