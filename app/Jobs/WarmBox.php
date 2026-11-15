<?php

namespace App\Jobs;

use App\Fire\Explorer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Precomputes a box's 26-year analysis so its owner never waits for the fit again. */
class WarmBox implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /** @param array{0: float, 1: float, 2: float, 3: float} $bbox */
    public function __construct(public array $bbox) {}

    public function handle(Explorer $explorer): void
    {
        if (Explorer::reject($this->bbox) === null) {
            $explorer->analyze($this->bbox);
        }
    }
}
