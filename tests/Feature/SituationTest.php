<?php

namespace Tests\Feature;

use App\Fire\Situation;
use App\Models\Alert;
use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SituationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $cuba = Area::query()->where('slug', 'cuba')->firstOrFail();
        Alert::query()->create(['area_id' => $cuba->id, 'week_start' => now()->startOfWeek()->subWeeks(2)->toDateString(), 'observed' => 490, 'typical' => 20, 'spread' => 6, 'score' => 78.3, 'severity' => 'extreme']);
        config()->set('services.bulletin.url', 'https://ollama.test');
        config()->set('services.bulletin.model', 'gemma4:test');
    }

    public function test_the_reading_uses_the_model_only_when_every_number_is_a_fact(): void
    {
        Http::fakeSequence('https://ollama.test/api/chat')
            ->push(['message' => ['content' => 'Cuba deserves attention: 24.5 times normal two weeks ago, inside its fire season. The other areas are quiet.']])
            ->push(['message' => ['content' => 'Cuba is 123456 times normal.']]);
        $reading = app(Situation::class)->for('en');
        $this->assertSame('ai', $reading['source']);
        $cuba = collect($reading['facts']['areas'])->firstWhere('area', 'Cuba');
        $this->assertSame(24.5, $cuba['unusual_weeks_last_60_days'][0]['times_normal']);

        Cache::flush();
        $reading = app(Situation::class)->for('en');
        $this->assertSame('template', $reading['source']);
        $this->assertStringContainsString('Cuba', $reading['text']);
        $this->assertStringContainsString('24.5 times normal', $reading['text']);
    }

    public function test_the_unusual_periods_page_shows_the_reading_without_waiting_on_the_model(): void
    {
        Http::fake(fn () => throw new \RuntimeException('the page must not call the model'));

        $this->get('/alerts')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Alerts')
            ->where('situation.source', 'template')
            ->where('situation.text', fn ($t) => str_contains($t, 'Cuba')));
    }
}
