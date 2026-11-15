<?php

namespace App\Console\Commands;

use App\Fire\FirmsFeed;
use App\Fire\Pipeline;
use App\Models\Area;
use App\Models\ZoneRequest;
use Illuminate\Console\Command;

/**
 * Loads NASA FIRMS archive CSV files (the ones the FIRMS download page emails) into the detections table,
 * streaming them in chunks so a multi-GB file fits in memory, then optionally processes the area.
 */
class ImportCommand extends Command
{
    protected $signature = 'fire:import {files* : FIRMS archive CSV files} {--area= : process this area afterwards} {--chunk=50000 : rows per batch}';

    protected $description = 'Import NASA FIRMS archive CSV files (MODIS or VIIRS) and, with --area, build that area';

    public function handle(FirmsFeed $feed, Pipeline $pipeline): int
    {
        $area = $this->option('area') ? Area::query()->where('slug', $this->option('area'))->first() : null;

        if ($this->option('area') && ! $area) {
            $this->error("No area '{$this->option('area')}'. Create it first with fire:area.");

            return self::FAILURE;
        }

        $totals = ['read' => 0, 'kept' => 0, 'inserted' => 0];
        $started = microtime(true);

        foreach ($this->argument('files') as $file) {
            if (! is_readable($file)) {
                $this->error("Cannot read {$file}");

                return self::FAILURE;
            }

            $handle = fopen($file, 'r');
            $header = rtrim((string) fgets($handle), "\r\n");
            $lines = [];

            $flush = function () use (&$lines, &$totals, $feed, $header) {
                if ($lines === []) {
                    return;
                }
                $result = $feed->import($header."\n".implode("\n", $lines));
                $lines = [];
                foreach (['read', 'kept', 'inserted'] as $k) {
                    $totals[$k] += $result[$k];
                }
            };

            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    continue;
                }
                $lines[] = $line;
                if (count($lines) >= (int) $this->option('chunk')) {
                    $flush();
                    $this->output->write("\r{$file}: ".number_format($totals['read']).' rows read, '.number_format($totals['inserted']).' new');
                }
            }

            $flush();
            fclose($handle);
            $this->output->writeln("\r{$file}: ".number_format($totals['read']).' rows read, '.number_format($totals['inserted']).' new');
        }

        $this->info(sprintf('Imported %s new detections (%s inside the areas) in %d s.', number_format($totals['inserted']), number_format($totals['kept']), microtime(true) - $started));

        if ($area) {
            $this->line("Processing {$area->slug}…");
            $pipeline->run('2000-11-01', now()->toDateString(), fn (string $m) => $this->line($m), true, [$area->slug]);
            ZoneRequest::query()
                ->where('status', ZoneRequest::REQUESTED)
                ->where('west', '>=', $area->west)->where('east', '<=', $area->east)
                ->where('south', '>=', $area->south)->where('north', '<=', $area->north)
                ->update(['status' => ZoneRequest::LOADED, 'area_id' => $area->id]);
            $this->call('fire:warm');
        }

        return self::SUCCESS;
    }
}
