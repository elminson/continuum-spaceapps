<?php

namespace App\Mail;

use App\Fire\FireAlert;
use App\Models\AlertSubscription;
use Carbon\CarbonImmutable;
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
        $times = $this->times();
        $subject = $es
            ? "Alerta de fuego · {$this->subscription->name}: ×{$times} de lo normal"
            : "Fire alert · {$this->subscription->name}: ×{$times} normal";

        return new Envelope(subject: $this->test ? ($es ? "[Prueba] {$subject}" : "[Test] {$subject}") : $subject);
    }

    public function content(): Content
    {
        $es = $this->subscription->locale === 'es';
        $bbox = implode(',', $this->subscription->bbox());
        $fmt = fn (float|int $n, int $d = 0) => number_format($n, $d, $es ? ',' : '.', $es ? '.' : ',');
        $week = CarbonImmutable::parse($this->alert['week_start']);
        $levels = $es ? ['elevated' => 'Elevado', 'high' => 'Alto', 'extreme' => 'Extremo'] : ['elevated' => 'Elevated', 'high' => 'High', 'extreme' => 'Extreme'];
        $text = FireAlert::text($this->subscription, $this->alert);

        return new Content(view: 'emails.fire-alert', with: [
            'es' => $es,
            'name' => $this->subscription->name,
            'weekLabel' => $es ? 'semana del '.$week->locale('es')->translatedFormat('j \d\e F \d\e Y') : 'week of '.$week->format('F j, Y'),
            'times' => $this->times(),
            'observed' => $fmt($this->alert['observed']),
            'typical' => $fmt($this->alert['typical']),
            'severity' => $this->alert['severity'],
            'severityLabel' => str_replace(':s', $fmt($this->alert['score'], 1), $levels[$this->alert['severity']] ?? $this->alert['severity']),
            'text' => $text,
            'preheader' => str($text)->limit(110)->toString(),
            'mapUrl' => url("/explore?bbox={$bbox}&week={$this->alert['week_start']}"),
            'methodUrl' => url('/alerts/subscribe#how'),
            'stopUrl' => $this->subscription->exists ? url("/subscriptions/{$this->subscription->token}") : url('/alerts/subscribe'),
            'siteUrl' => url('/'),
            'test' => $this->test,
        ]);
    }

    private function times(): string
    {
        $es = $this->subscription->locale === 'es';
        $ratio = $this->alert['times'] ?? ($this->alert['typical'] > 0 ? round($this->alert['observed'] / $this->alert['typical'], 1) : 0);

        return number_format((float) $ratio, 1, $es ? ',' : '.', $es ? '.' : ',');
    }
}
