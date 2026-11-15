<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A zone outside the loaded areas that someone asked us to load from the NASA FIRMS archive. */
class ZoneRequest extends Model
{
    public const REQUESTED = 'requested';

    public const LOADED = 'loaded';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['west' => 'float', 'south' => 'float', 'east' => 'float', 'north' => 'float'];
    }

    /**
     * How long loading a zone takes, from its size: NASA prepares the archive in minutes to an hour,
     * then importing and harmonizing here runs at roughly 10 minutes per square degree-decade of record.
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}  $bbox
     * @return array{nasa: string, ours_minutes: int}
     */
    public static function estimate(array $bbox): array
    {
        $squareDegrees = max(0.01, ($bbox[2] - $bbox[0]) * ($bbox[3] - $bbox[1]));

        return ['nasa' => 'minutes to an hour', 'ours_minutes' => (int) min(240, max(10, round($squareDegrees * 4)))];
    }
}
