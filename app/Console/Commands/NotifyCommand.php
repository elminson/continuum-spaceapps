<?php

namespace App\Console\Commands;

use App\Fire\FireAlert;
use App\Jobs\SendFireAlert;
use App\Models\AlertSubscription;
use Illuminate\Console\Command;

class NotifyCommand extends Command
{
    protected $signature = 'fire:notify';

    protected $description = 'Queue a fire alert email for every subscribed box whose latest week is unusual and not yet reported';

    public function handle(FireAlert $alerts): int
    {
        $queued = 0;

        AlertSubscription::query()->orderBy('id')->each(function (AlertSubscription $s) use ($alerts, &$queued) {
            $alert = $alerts->current($s);

            if ($alert === null || $s->last_alert_week?->toDateString() === $alert['week_start']) {
                return;
            }

            SendFireAlert::dispatch($s, $alert);
            $queued++;
            $this->line("{$s->name} <{$s->email}>: week of {$alert['week_start']} queued");
        });

        $this->info("{$queued} alert(s) queued");

        return self::SUCCESS;
    }
}
