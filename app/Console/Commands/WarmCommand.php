<?php

namespace App\Console\Commands;

use App\Fire\Insights;
use App\Fire\Sensors;
use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WarmCommand extends Command
{
    protected $signature = 'fire:warm';

    protected $description = 'Precompute the series, retirement tests and totals so no visitor waits for them';

    public function handle(Insights $insights): int
    {
        cache()->put('detections:count', (int) DB::table('detections')->count(), now()->addHour());

        foreach (Area::all() as $area) {
            $this->components->task($area->slug, function () use ($insights, $area) {
                $insights->monthly($area, Sensors::MODIS);
                $insights->monthly($area, Sensors::VIIRS);
                $insights->retirement($area, null);
            });
        }

        return self::SUCCESS;
    }
}
