<?php

namespace App\Fire;

use App\Models\Area;
use Carbon\CarbonImmutable;

/** Raw pixels → grid → area days → calibration → alerts, for a date range. */
class Pipeline
{
    public function __construct(
        private BuildGrid $grid,
        private BuildAreaDays $areaDays,
        private Calibrate $calibrate,
        private DetectAlerts $alerts,
    ) {}

    public function run(string $from, string $to, ?callable $log = null): void
    {
        $log ??= fn (string $message) => null;

        for ($start = CarbonImmutable::parse($from); $start->lte($to); $start = $start->addYear()->startOfYear()) {
            $end = min($start->endOfYear()->toDateString(), $to);
            $log("Grid {$start->toDateString()} → {$end}");
            ($this->grid)($start->toDateString(), $end);
        }

        foreach (Area::all() as $area) {
            $log("Area {$area->slug}");
            ($this->areaDays)($area, $from, $to);
            ($this->calibrate)($area);
            $count = ($this->alerts)($area);
            $area->forceFill(['processed_at' => now()])->save();
            $log("  {$count} unusual weeks");
        }

    }
}
