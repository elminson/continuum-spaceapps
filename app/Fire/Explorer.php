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
        // The 32 provinces of the Dominican Republic (bounding boxes from geoBoundaries ADM1, CC BY 4.0).
        'dr-azua' => ['Azua', 'Azua', [-71.17, 18.25, -70.47, 18.99], 'province'],
        'dr-bahoruco' => ['Bahoruco', 'Bahoruco', [-71.63, 18.33, -70.99, 18.66], 'province'],
        'dr-barahona' => ['Barahona', 'Barahona', [-71.43, 17.84, -70.95, 18.53], 'province'],
        'dr-dajabon' => ['Dajabón', 'Dajabón', [-71.78, 19.21, -71.41, 19.65], 'province'],
        'dr-distrito-nacional' => ['Distrito Nacional', 'Distrito Nacional', [-70.0, 18.42, -69.87, 18.54], 'province'],
        'dr-duarte' => ['Duarte', 'Duarte', [-70.37, 19.03, -69.67, 19.5], 'province'],
        'dr-el-seibo' => ['El Seibo', 'El Seibo', [-69.33, 18.52, -68.78, 19.06], 'province'],
        'dr-elias-pina' => ['Elías Piña', 'Elías Piña', [-71.87, 18.65, -71.32, 19.3], 'province'],
        'dr-espaillat' => ['Espaillat', 'Espaillat', [-70.62, 19.31, -70.09, 19.72], 'province'],
        'dr-hato-mayor' => ['Hato Mayor', 'Hato Mayor', [-69.64, 18.52, -69.19, 19.12], 'province'],
        'dr-hermanas-mirabal' => ['Hermanas Mirabal', 'Hermanas Mirabal', [-70.47, 19.22, -70.23, 19.56], 'province'],
        'dr-independencia' => ['Independencia', 'Independencia', [-72.02, 18.13, -71.2, 18.69], 'province'],
        'dr-la-altagracia' => ['La Altagracia', 'La Altagracia', [-68.95, 18.1, -68.32, 18.98], 'province'],
        'dr-la-romana' => ['La Romana', 'La Romana', [-69.13, 18.39, -68.85, 18.67], 'province'],
        'dr-la-vega' => ['La Vega', 'La Vega', [-70.92, 18.67, -70.27, 19.37], 'province'],
        'dr-maria-trinidad-sanchez' => ['María Trinidad Sánchez', 'María Trinidad Sánchez', [-70.22, 19.19, -69.77, 19.68], 'province'],
        'dr-monsenor-nouel' => ['Monseñor Nouel', 'Monseñor Nouel', [-70.6, 18.72, -70.22, 19.12], 'province'],
        'dr-monte-cristi' => ['Monte Cristi', 'Monte Cristi', [-71.78, 19.53, -71.13, 19.91], 'province'],
        'dr-monte-plata' => ['Monte Plata', 'Monte Plata', [-70.26, 18.57, -69.38, 19.09], 'province'],
        'dr-pedernales' => ['Pedernales', 'Pedernales', [-71.78, 17.54, -71.28, 18.3], 'province'],
        'dr-peravia' => ['Peravia', 'Peravia', [-70.58, 18.2, -70.18, 18.51], 'province'],
        'dr-puerto-plata' => ['Puerto Plata', 'Puerto Plata', [-71.23, 19.51, -70.37, 19.94], 'province'],
        'dr-samana' => ['Samaná', 'Samaná', [-69.8, 19.0, -69.15, 19.37], 'province'],
        'dr-san-cristobal' => ['San Cristóbal', 'San Cristóbal', [-70.38, 18.23, -70.0, 18.81], 'province'],
        'dr-san-jose-de-ocoa' => ['San José de Ocoa', 'San José de Ocoa', [-70.7, 18.41, -70.34, 18.79], 'province'],
        'dr-san-juan' => ['San Juan', 'San Juan', [-71.64, 18.6, -70.89, 19.2], 'province'],
        'dr-san-pedro-de-macoris' => ['San Pedro de Macorís', 'San Pedro de Macorís', [-69.62, 18.39, -69.08, 18.81], 'province'],
        'dr-santiago' => ['Santiago (province)', 'Santiago (provincia)', [-71.25, 19.02, -70.54, 19.66], 'province'],
        'dr-santiago-rodriguez' => ['Santiago Rodríguez', 'Santiago Rodríguez', [-71.52, 19.17, -71.06, 19.57], 'province'],
        'dr-santo-domingo' => ['Santo Domingo (province)', 'Santo Domingo (provincia)', [-70.17, 18.41, -69.51, 18.74], 'province'],
        'dr-sanchez-ramirez' => ['Sánchez Ramírez', 'Sánchez Ramírez', [-70.4, 18.85, -69.9, 19.16], 'province'],
        'dr-valverde' => ['Valverde', 'Valverde', [-71.19, 19.43, -70.86, 19.76], 'province'],
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

        return Cache::remember(self::key($bbox), now()->addDay(), function () use ($cells, $west, $south, $east, $north) {
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
                'alerts' => array_map(fn ($a) => [...$a, 'times' => $a['typical'] > 0 ? round($a['observed'] / $a['typical'], 1) : null], array_slice($alerts, 0, 300)),
            ];
        });
    }

    /**
     * The ready-made places, with their strongest unusual week when it is already computed.
     *
     * @return list<array{slug: string, name: string, bbox: array{0: float, 1: float, 2: float, 3: float}, top_week: ?string, top_times: ?float, group: string}>
     */
    public static function places(): array
    {
        $es = app()->getLocale() === 'es';

        return array_map(fn (string $slug, array $p) => [
            'slug' => $slug,
            'name' => $es ? $p[1] : $p[0],
            'bbox' => $p[2],
            'top_week' => Cache::get(self::key($p[2]))['alerts'][0]['week_start'] ?? null,
            'top_times' => Cache::get(self::key($p[2]))['alerts'][0]['times'] ?? null,
            'group' => $p[3] ?? 'place',
        ], array_keys(self::PLACES), self::PLACES);
    }

    /**
     * Alerts of a ready-made place, only if already computed (never blocks a page).
     *
     * @return list<array<string, mixed>>|null
     */
    public static function cachedAlerts(string $slug): ?array
    {
        $place = self::PLACES[$slug] ?? null;
        $result = $place ? Cache::get(self::key($place[2])) : null;

        return $result['alerts'] ?? null;
    }

    /** @param  array{0: float, 1: float, 2: float, 3: float}  $bbox */
    private static function key(array $bbox): string
    {
        [$west, $south, $east, $north] = array_map(fn ($v) => round((float) $v, 2), $bbox);
        $cells = [(int) floor($south * 100), (int) ceil($north * 100), (int) floor($west * 100), (int) ceil($east * 100)];

        return 'explore:v2:'.implode(':', $cells).':'.(Area::query()->max('processed_at') ?? '');
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

        return $inside ? null : 'Draw the box inside one of the dashed areas.';
    }
}
