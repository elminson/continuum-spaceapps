<?php

namespace App\Console\Commands;

use App\Models\Area;
use Illuminate\Console\Command;

class AreaCommand extends Command
{
    protected $signature = 'fire:area {slug} {name} {west} {south} {east} {north} {--name-es= : Spanish name}';

    protected $description = 'Create (or update) an area of interest; then import its NASA FIRMS archive with fire:import';

    public function handle(): int
    {
        $bbox = array_map(fn ($k) => round((float) $this->argument($k), 3), ['west', 'south', 'east', 'north']);

        if ($bbox[0] >= $bbox[2] || $bbox[1] >= $bbox[3]) {
            $this->error('The box is empty: west < east and south < north.');

            return self::FAILURE;
        }

        $area = Area::query()->updateOrCreate(['slug' => $this->argument('slug')], [
            'name' => $this->argument('name'),
            'name_es' => $this->option('name-es') ?: $this->argument('name'),
            'west' => $bbox[0], 'south' => $bbox[1], 'east' => $bbox[2], 'north' => $bbox[3],
        ]);

        $this->info("Area {$area->slug} saved ({$bbox[0]}, {$bbox[1]}) → ({$bbox[2]}, {$bbox[3]}).");
        $this->line('Next: request the archive at https://firms.modaps.eosdis.nasa.gov/download/ for this box (MODIS C6.1 from 2000-11-01, VIIRS S-NPP, NOAA-20, NOAA-21 from 2012-01-20),');
        $this->line("then: php artisan fire:import <file.csv> ... --area={$area->slug} && php artisan fire:process --area={$area->slug} && php artisan fire:warm");

        return self::SUCCESS;
    }
}
