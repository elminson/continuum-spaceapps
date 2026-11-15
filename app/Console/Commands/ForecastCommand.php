<?php

namespace App\Console\Commands;

use App\Fire\Insights;
use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ForecastCommand extends Command
{
    protected $signature = 'fire:forecast {--area=* : only these slugs} {--fresh : recompute even if cached today}';

    protected $description = 'Fit the next-week fire forecast of each area and print its validation';

    public function handle(Insights $insights): int
    {
        $areas = Area::query()->ordered()->when($this->option('area'), fn ($q, $slugs) => $q->whereIn('slug', $slugs))->get();

        foreach ($areas as $area) {
            if ($this->option('fresh')) {
                Cache::forget("insights:{$area->id}:forecast:".now()->format('Y-m-d').':'.($area->processed_at?->timestamp ?? 0));
            }

            $started = microtime(true);
            $f = $insights->forecast($area);

            if ($f === null) {
                $this->warn("{$area->name}: not enough record or weather yet");

                continue;
            }

            $v = $f['validation'];
            $this->line(sprintf('%s: week of %s expected %s (×%s normal, %s–%s) · skill vs climatology %+d%%, vs persistence %+d%%, r %.2f on %d weeks · weather through %s · %.1fs',
                $area->name, $f['week_start'], $f['expected'], $f['times_normal'] ?? '–', $f['low'], $f['high'],
                round($v['skill_vs_climatology'] * 100), round($v['skill_vs_persistence'] * 100), $v['correlation'], $v['weeks'], $f['weather_through'], microtime(true) - $started));
        }

        return self::SUCCESS;
    }
}
