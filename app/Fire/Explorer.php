<?php

namespace App\Fire;

use App\Models\Area;
use Illuminate\Support\Facades\Cache;

/**
 * Instant analysis of any box inside the processed areas, straight from
 * the 1 km grid: its own fit, held-out test, calendar and unusual weeks.
 */
class Explorer
{
    public const MAX_DEGREES = 8.0;

    /** Ready-made places, precomputed after every update so they open instantly. */
    public const PLACES = [
        'dominican-republic' => ['Dominican Republic', 'República Dominicana', [-72.01, 17.47, -68.32, 19.93]],
        'haiti' => ['Haiti', 'Haití', [-74.48, 18.02, -71.62, 20.09]],
        'santo-domingo' => ['Santo Domingo', 'Santo Domingo', [-70.2, 18.35, -69.4, 18.75]],
        'santiago' => ['Santiago (DR)', 'Santiago (RD)', [-71.2, 19.2, -70.5, 19.6]],
        'puerto-rico' => ['Puerto Rico', 'Puerto Rico', [-67.3, 17.88, -65.58, 18.53]],
        'western-cuba' => ['Western Cuba', 'Occidente de Cuba', [-84.5, 21.7, -81.0, 23.3]],
        'eastern-cuba' => ['Eastern Cuba', 'Oriente de Cuba', [-77.5, 19.8, -74.25, 21.3]],
    ];

    public function __construct(private DailyRecord $record) {}

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float}  $bbox  west, south, east, north
     * @return array<string, mixed>
     */
    public function analyze(array $bbox): array
    {
        [$west, $south, $east, $north] = array_map(fn ($v) => round((float) $v, 2), $bbox);
        $cells = [(int) floor($south * 100), (int) ceil($north * 100), (int) floor($west * 100), (int) ceil($east * 100)];
        $key = 'explore:'.implode(':', $cells).':'.(Area::query()->max('processed_at') ?? '');

        return Cache::remember($key, now()->addDay(), function () use ($cells, $west, $south, $east, $north) {
            $days = $this->record->forCells($cells);
            $pairs = Calibrate::overlap($days);

            if (count($pairs) < 365 || array_sum(array_column($pairs, 'viirs')) === 0) {
                return ['available' => false, 'bbox' => [$west, $south, $east, $north]];
            }

            $fits = Calibrate::fit($pairs);
            $daily = Harmonizer::apply($days, $fits, Sensors::MODIS);
            $alerts = DetectAlerts::find($daily);
            usort($alerts, fn ($a, $b) => $b['score'] <=> $a['score']);

            return [
                'available' => true,
                'bbox' => [$west, $south, $east, $north],
                'fit' => ['ratio' => round($fits[0]['ratio'], 4), 'low' => round($fits[0]['low'], 4), 'high' => round($fits[0]['high'], 4), 'days' => $fits[0]['days'], 'validation' => Calibrate::validate($pairs), 'months' => []],
                'monthly' => Harmonizer::toMonths($daily),
                'alerts' => array_map(fn ($a) => [...$a, 'times' => $a['typical'] > 0 ? round($a['observed'] / $a['typical'], 1) : null], array_slice($alerts, 0, 10)),
            ];
        });
    }

    /** @return list<array{slug: string, name: string, bbox: array{0: float, 1: float, 2: float, 3: float}}> */
    public static function places(): array
    {
        $es = app()->getLocale() === 'es';

        return array_map(fn (string $slug, array $p) => ['slug' => $slug, 'name' => $es ? $p[1] : $p[0], 'bbox' => $p[2]], array_keys(self::PLACES), self::PLACES);
    }

    /** @return string|null why a box cannot be analysed */
    public static function reject(array $bbox): ?string
    {
        [$west, $south, $east, $north] = $bbox;

        if ($west >= $east || $south >= $north) {
            return 'The box is empty.';
        }

        if ($east - $west > self::MAX_DEGREES || $north - $south > self::MAX_DEGREES) {
            return 'Keep the box under 8° on each side.';
        }

        $inside = Area::all()->contains(fn (Area $a) => $west >= $a->west && $east <= $a->east && $south >= $a->south && $north <= $a->north);

        return $inside ? null : 'Draw the box inside one of the four areas.';
    }
}
