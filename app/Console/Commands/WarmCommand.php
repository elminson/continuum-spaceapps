<?php

namespace App\Console\Commands;

use App\Fire\Bulletin;
use App\Fire\DataStatus;
use App\Fire\Explorer;
use App\Fire\Insights;
use App\Fire\Sensors;
use App\Fire\Situation;
use App\Models\AlertSubscription;
use App\Models\Area;
use App\Models\SavedBox;
use Illuminate\Console\Command;

class WarmCommand extends Command
{
    protected $signature = 'fire:warm';

    protected $description = 'Precompute the series, retirement tests and totals so no visitor waits for them';

    public function handle(Insights $insights, Explorer $explorer, DataStatus $status, Bulletin $bulletin, Situation $situation): int
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

        // Bulletins for the focus area: its strongest week and the last 30 days, in both languages.
        $focus = Area::query()->where('slug', Area::FOCUS)->first();
        if ($focus) {
            $this->components->task('Bulletins', function () use ($bulletin, $focus) {
                $top = $focus->alerts()->orderByDesc('score')->first()?->week_start?->toDateString();
                foreach (['en', 'es'] as $locale) {
                    $bulletin->for($focus, $top, $locale);
                    $bulletin->for($focus, null, $locale);
                }
            });
        }

        $this->components->task('Situation reading', function () use ($situation) {
            foreach (['en', 'es'] as $locale) {
                $situation->for($locale);
            }
        });

        foreach (Explorer::PLACES as $slug => [$name, $nameEs, $bbox]) {
            $this->components->task($name, fn () => $explorer->analyze($bbox));
        }

        // Boxes people saved or subscribed to: warm them so their owners never wait for the fit.
        $boxes = SavedBox::query()->get()->map(fn ($b) => [$b->west, $b->south, $b->east, $b->north])
            ->merge(AlertSubscription::query()->get()->map(fn ($s) => $s->bbox()))
            ->map(fn ($b) => array_map(fn ($v) => round((float) $v, 2), $b))
            ->unique(fn ($b) => implode(',', $b))
            ->filter(fn ($b) => Explorer::reject($b) === null);
        $this->components->task("User boxes ({$boxes->count()})", function () use ($boxes, $explorer) {
            foreach ($boxes as $bbox) {
                $explorer->analyze($bbox);
            }
        });

        return self::SUCCESS;
    }
}
