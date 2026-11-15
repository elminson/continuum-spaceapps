<?php

namespace App\Fire;

use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Daily weather at the centre of each area from NASA POWER (free, no key):
 * air temperature, relative humidity, rainfall and wind, the drivers of fire.
 */
class Weather
{
    public const URL = 'https://power.larc.nasa.gov/api/temporal/daily/point';

    public const PARAMETERS = ['T2M' => 't2m', 'RH2M' => 'rh2m', 'PRECTOTCORR' => 'precip', 'WS2M' => 'ws2m'];

    /** Fetches [$from, $to] for an area and stores it; returns the number of days with data. */
    public function pull(Area $area, string $from, string $to): int
    {
        $response = Http::timeout(120)->retry(2, 5000)->get(self::URL, [
            'parameters' => implode(',', array_keys(self::PARAMETERS)),
            'community' => 'AG',
            'longitude' => round(($area->west + $area->east) / 2, 3),
            'latitude' => round(($area->south + $area->north) / 2, 3),
            'start' => str_replace('-', '', $from),
            'end' => str_replace('-', '', $to),
            'format' => 'JSON',
        ])->throw()->json();

        return $this->store($area, $response);
    }

    /** @param array<string, mixed> $response a POWER daily point response */
    public function store(Area $area, array $response): int
    {
        $fill = (float) ($response['header']['fill_value'] ?? -999);
        $series = $response['properties']['parameter'] ?? [];
        $rows = [];

        foreach ($series['T2M'] ?? [] as $yyyymmdd => $unused) {
            $row = ['area_id' => $area->id, 'day' => substr($yyyymmdd, 0, 4).'-'.substr($yyyymmdd, 4, 2).'-'.substr($yyyymmdd, 6, 2)];
            $any = false;

            foreach (self::PARAMETERS as $param => $column) {
                $value = $series[$param][$yyyymmdd] ?? null;
                $row[$column] = $value === null || (float) $value <= $fill ? null : round((float) $value, 2);
                $any = $any || $row[$column] !== null;
            }

            if ($any) {
                $rows[] = $row;
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('weather_days')->upsert($chunk, ['area_id', 'day'], array_values(self::PARAMETERS));
        }

        return count($rows);
    }

    /** @return array<string, array{t2m: ?float, rh2m: ?float, precip: ?float, ws2m: ?float}> day → values */
    public function days(Area $area): array
    {
        $out = [];

        foreach (DB::table('weather_days')->where('area_id', $area->id)->orderBy('day')->get() as $r) {
            $out[$r->day] = ['t2m' => $r->t2m === null ? null : (float) $r->t2m, 'rh2m' => $r->rh2m === null ? null : (float) $r->rh2m, 'precip' => $r->precip === null ? null : (float) $r->precip, 'ws2m' => $r->ws2m === null ? null : (float) $r->ws2m];
        }

        return $out;
    }

    public function lastDay(Area $area): ?string
    {
        return DB::table('weather_days')->where('area_id', $area->id)->whereNotNull('t2m')->max('day');
    }

    /** The first day POWER should cover: the start of the fire record. */
    public static function start(): string
    {
        return '2000-11-01';
    }

    /** Yesterday in UTC; POWER publishes with a lag of a few days, missing days come back as fill values. */
    public static function end(): string
    {
        return CarbonImmutable::today()->subDay()->toDateString();
    }
}
