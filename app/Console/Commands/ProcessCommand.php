<?php

namespace App\Console\Commands;

use App\Fire\Pipeline;
use App\Fire\Sensors;
use Illuminate\Console\Command;

class ProcessCommand extends Command
{
    protected $signature = 'fire:process {--from= : first day (default: start of the MODIS record)} {--to= : last day (default: today)} {--area=* : only these area slugs} {--skip-grid : reuse the existing 1 km grid}';

    protected $description = 'Grid the raw detections, then rebuild area totals, the MODIS/VIIRS fit and unusual weeks';

    public function handle(Pipeline $pipeline): int
    {
        $pipeline->run(
            (string) ($this->option('from') ?: Sensors::MODIS_START),
            (string) ($this->option('to') ?: now()->toDateString()),
            fn (string $message) => $this->line($message),
            ! $this->option('skip-grid'),
            $this->option('area') ?: null,
        );

        return self::SUCCESS;
    }
}
