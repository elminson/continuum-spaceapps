<?php

namespace App\Console\Commands;

use App\Fire\DataStatus;
use App\Fire\Explorer;
use App\Fire\Insights;
use App\Fire\Sensors;
use App\Models\Area;
use Illuminate\Console\Command;

class WarmCommand extends Command
{
    protected $signature = 'fire:warm';

    protected $description = 'Precompute the series, retirement tests and totals so no visitor waits for them';

    public function handle(Insights $insights, Explorer $explorer, DataStatus $status): int
    {
        $this->components->task('Data status', fn () => $status->refresh());

        foreach (Area::all() as $area) {
            $this->components->task($area->slug, function () use ($insights, $area) {
                $insights->monthly($area, Sensors::MODIS);
                $insights->monthly($area, Sensors::VIIRS);
                $insights->retirement($area, null);
                $insights->season($area);
            });
        }

        foreach (Explorer::PLACES as $slug => [$name, $nameEs, $bbox]) {
            $this->components->task($name, fn () => $explorer->analyze($bbox));
        }

        return self::SUCCESS;
    }
}
