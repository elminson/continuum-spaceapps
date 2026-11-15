<?php

namespace App\Mail;

use App\Fire\FireAlert;
use App\Models\AlertSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FireAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $alert */
    public function __construct(public AlertSubscription $subscription, public array $alert, public bool $test = false) {}

    public function envelope(): Envelope
    {
        $es = $this->subscription->locale === 'es';
        $subject = $es ? "Alerta de fuego · {$this->subscription->name}" : "Fire alert · {$this->subscription->name}";

        return new Envelope(subject: $this->test ? ($es ? "[Prueba] {$subject}" : "[Test] {$subject}") : $subject);
    }

    public function content(): Content
    {
        $bbox = implode(',', $this->subscription->bbox());

        return new Content(view: 'emails.fire-alert', with: [
            'es' => $this->subscription->locale === 'es',
            'name' => $this->subscription->name,
            'text' => FireAlert::text($this->subscription, $this->alert),
            'severity' => $this->alert['severity'],
            'mapUrl' => url("/explore?bbox={$bbox}&week={$this->alert['week_start']}"),
            'methodUrl' => url('/alerts/subscribe#how'),
            'stopUrl' => url("/subscriptions/{$this->subscription->token}"),
            'test' => $this->test,
        ]);
    }
}
