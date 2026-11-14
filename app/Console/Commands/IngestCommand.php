<?php

namespace App\Console\Commands;

use App\Fire\FirmsFeed;
use App\Fire\Pipeline;
use Illuminate\Console\Command;

class IngestCommand extends Command
{
    protected $signature = 'fire:ingest';

    protected $description = 'Pull the last 24 h of NASA FIRMS MODIS and VIIRS fires and reprocess the recent days';

    public function handle(FirmsFeed $feed, Pipeline $pipeline): int
    {
        $from = null;

        foreach (FirmsFeed::FEEDS as $name => $url) {
            try {
                $result = $feed->pull($url);
                $this->line("{$name}: {$result['read']} read, {$result['kept']} in areas, {$result['inserted']} new");
                $from = $result['from'] && (! $from || $result['from'] < $from) ? $result['from'] : $from;
            } catch (\Throwable $e) {
                $this->error("{$name}: {$e->getMessage()}");
            }
        }

        if ($from) {
            $pipeline->run($from, now()->toDateString());
            $this->call('fire:warm');
        }

        return self::SUCCESS;
    }
}
