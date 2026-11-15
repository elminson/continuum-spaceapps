<?php

namespace Tests\Feature;

use App\Fire\Explorer;
use App\Jobs\SendFireAlert;
use App\Mail\FireAlertMail;
use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private const ALERT = ['week_start' => '2015-04-27', 'observed' => 229.0, 'typical' => 20.0, 'spread' => 5.0, 'score' => 41.8, 'severity' => 'extreme', 'times' => 11.5];

    protected function setUp(): void
    {
        parent::setUp();
        Area::query()->create(['slug' => 'test', 'name' => 'Test', 'name_es' => 'Prueba', 'west' => -71, 'south' => 18, 'east' => -70, 'north' => 19]);
    }

    public function test_anyone_can_subscribe_a_box_inside_the_areas_and_send_a_test_alert(): void
    {
        Queue::fake();
        $this->mock(Explorer::class)->shouldReceive('analyze')->andReturn(['available' => true, 'bbox' => [-70.8, 18.2, -70.3, 18.6], 'alerts' => [self::ALERT]]);

        $this->post('/subscriptions', ['name' => 'Home', 'email' => 'me@example.com', 'bbox' => [-80, 18.2, -70.3, 18.6]])
            ->assertSessionHasErrors('bbox');

        $response = $this->post('/subscriptions', ['name' => 'Home', 'email' => 'me@example.com', 'phone' => '+1 809 000 0000', 'bbox' => [-70.8, 18.2, -70.3, 18.6]]);
        $s = AlertSubscription::query()->firstOrFail();
        $response->assertRedirect("/subscriptions/{$s->token}");

        $this->get("/subscriptions/{$s->token}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Subscription')
            ->where('subscription.email', 'me@example.com')
            ->where('strongest.week_start', '2015-04-27')
            ->where('preview', fn ($text) => str_contains($text, '229') && str_contains($text, '11.5')));

        $this->post("/subscriptions/{$s->token}/test")->assertRedirect();
        Queue::assertPushed(SendFireAlert::class, fn (SendFireAlert $job) => $job->test && $job->subscription->is($s));

        $this->delete("/subscriptions/{$s->token}")->assertRedirect('/alerts/subscribe');
        $this->assertDatabaseCount('alert_subscriptions', 0);
    }

    public function test_the_daily_check_queues_one_email_per_newly_flagged_week(): void
    {
        Queue::fake();
        $recent = ['week_start' => now()->startOfWeek()->subWeek()->toDateString()] + self::ALERT;
        $this->mock(Explorer::class)->shouldReceive('analyze')->andReturn(['available' => true, 'bbox' => [-70.8, 18.2, -70.3, 18.6], 'alerts' => [self::ALERT, $recent]]);
        $s = AlertSubscription::query()->create(['name' => 'Home', 'email' => 'me@example.com', 'west' => -70.8, 'south' => 18.2, 'east' => -70.3, 'north' => 18.6]);

        $this->artisan('fire:notify')->expectsOutputToContain('1 alert(s) queued')->assertSuccessful();
        Queue::assertPushed(SendFireAlert::class, 1);

        $s->forceFill(['last_alert_week' => $recent['week_start']])->save();
        $this->artisan('fire:notify')->expectsOutputToContain('0 alert(s) queued');
        Queue::assertPushed(SendFireAlert::class, 1);
    }

    public function test_the_alert_email_carries_the_numbers_and_the_links(): void
    {
        Mail::fake();
        $s = AlertSubscription::query()->create(['name' => 'Casa', 'email' => 'me@example.com', 'locale' => 'es', 'west' => -70.8, 'south' => 18.2, 'east' => -70.3, 'north' => 18.6]);

        (new SendFireAlert($s, self::ALERT))->handle();

        Mail::assertSent(FireAlertMail::class, function (FireAlertMail $mail) use ($s) {
            $html = html_entity_decode($mail->render());

            return $mail->hasTo('me@example.com')
                && str_contains($html, '11,5 veces')
                && str_contains($html, "/subscriptions/{$s->token}")
                && str_contains($html, 'bbox=-70.8,18.2,-70.3,18.6&week=2015-04-27');
        });
        $this->assertSame('2015-04-27', $s->fresh()->last_alert_week->toDateString());
    }

    public function test_anyone_can_get_a_sample_alert_five_times_a_day(): void
    {
        Queue::fake();
        $area = Area::query()->where('slug', 'test')->first();
        $area->update(['slug' => Area::FOCUS]);
        Alert::query()->create(['area_id' => $area->id, 'week_start' => '2015-04-27', 'observed' => 229, 'typical' => 20, 'spread' => 5, 'score' => 41.8, 'severity' => 'extreme']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/alerts/sample', ['email' => 'judge@example.com'])->assertSessionHas('status', fn (string $s) => str_contains($s, 'queued'));
        }
        $this->post('/alerts/sample', ['email' => 'judge@example.com'])->assertSessionHas('status', fn (string $s) => str_contains($s, 'limit resets tomorrow'));

        Queue::assertPushed(SendFireAlert::class, 5);
        Queue::assertPushed(SendFireAlert::class, fn (SendFireAlert $job) => $job->test && $job->target()->email === 'judge@example.com' && ! $job->target()->exists);
    }
}
