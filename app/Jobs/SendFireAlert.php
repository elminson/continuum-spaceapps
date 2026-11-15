<?php

namespace App\Jobs;

use App\Mail\FireAlertMail;
use App\Models\AlertSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/** Emails one unusual week to one subscriber. Phone alerts will follow the same path. */
class SendFireAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public ?AlertSubscription $subscription = null;

    /** A sample for an address that never subscribed: kept as plain attributes, since the queue restores models by id. */
    public ?array $draft = null;

    /** @param array<string, mixed> $alert */
    public function __construct(AlertSubscription $subscription, public array $alert, public bool $test = false)
    {
        if ($subscription->exists) {
            $this->subscription = $subscription;
        } else {
            $this->draft = $subscription->getAttributes();
        }
    }

    public function target(): AlertSubscription
    {
        return $this->subscription ?? new AlertSubscription($this->draft ?? []);
    }

    public function handle(): void
    {
        $target = $this->target();
        Mail::to($target->email)->send(new FireAlertMail($target, $this->alert, $this->test));

        if (! $this->test && $target->exists) {
            $target->forceFill(['last_alert_week' => $this->alert['week_start'], 'last_sent_at' => now()])->save();
        }
    }
}
