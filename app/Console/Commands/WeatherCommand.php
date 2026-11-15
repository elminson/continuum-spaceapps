<?php

namespace App\Console\Commands;

use App\Fire\Weather;
use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class WeatherCommand extends Command
{
    protected $signature = 'fire:weather {--area=* : only these slugs} {--all : fetch the whole record again}';

    protected $description = 'Pull daily weather at each area\'s centre from NASA POWER (full record the first time, then the last two weeks)';

    public function handle(Weather $weather): int
    {
        $areas = Area::query()->ordered()->when($this->option('area'), fn ($q, $slugs) => $q->whereIn('slug', $slugs))->get();

        foreach ($areas as $area) {
            $last = $this->option('all') ? null : $weather->lastDay($area);
            $from = $last ? CarbonImmutable::parse($last)->subDays(14)->toDateString() : Weather::start();

            $this->components->task("{$area->name} · {$from} → ".Weather::end(), function () use ($weather, $area, $from) {
                $weather->pull($area, $from, Weather::end());
            });
        }

        return self::SUCCESS;
    }
}
