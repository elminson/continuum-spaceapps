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

    /** @param array<string, mixed> $alert */
    public function __construct(public AlertSubscription $subscription, public array $alert, public bool $test = false) {}

    public function handle(): void
    {
        Mail::to($this->subscription->email)->send(new FireAlertMail($this->subscription, $this->alert, $this->test));

        if (! $this->test) {
            $this->subscription->forceFill(['last_alert_week' => $this->alert['week_start'], 'last_sent_at' => now()])->save();
        }
    }
}
