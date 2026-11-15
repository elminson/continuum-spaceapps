<?php

namespace App\Fire;

use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Pulls NASA FIRMS near-real-time global CSV feeds (no key needed), keeps
 * the pixels inside the areas and inserts the new ones.
 */
class FirmsFeed
{
    public const FEEDS = [
        'modis' => 'https://firms.modaps.eosdis.nasa.gov/data/active_fire/modis-c6.1/csv/MODIS_C6_1_Global_24h.csv',
        'noaa20' => 'https://firms.modaps.eosdis.nasa.gov/data/active_fire/noaa-20-viirs-c2/csv/J1_VIIRS_C2_Global_24h.csv',
        'noaa21' => 'https://firms.modaps.eosdis.nasa.gov/data/active_fire/noaa-21-viirs-c2/csv/J2_VIIRS_C2_Global_24h.csv',
    ];

    /** @return array{read: int, kept: int, inserted: int, from: ?string, to: ?string} */
    public function pull(string $url): array
    {
        $csv = Http::timeout(120)->retry(2, 5000)->get($url)->throw()->body();

        return $this->import($csv);
    }

    /** @return array{read: int, kept: int, inserted: int, from: ?string, to: ?string} */
    public function import(string $csv): array
    {
        $areas = Area::all();
        $lines = preg_split('/\r?\n/', trim($csv));
        $header = str_getcsv((string) array_shift($lines));
        $instrument = in_array('bright_ti4', $header, true) ? Sensors::VIIRS : Sensors::MODIS;
        $rows = [];
        $read = 0;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $read++;
            $r = array_combine($header, str_getcsv($line));
            $lat = (float) $r['latitude'];
            $lon = (float) $r['longitude'];

            if (! $areas->contains(fn (Area $a) => $lat >= $a->south && $lat <= $a->north && $lon >= $a->west && $lon <= $a->east)) {
                continue;
            }

            $time = str_pad($r['acq_time'], 4, '0', STR_PAD_LEFT);
            $rows[] = [
                'instrument' => $instrument,
                'satellite' => self::satellite($r['satellite']),
                'acquired_at' => $r['acq_date'].' '.substr($time, 0, 2).':'.substr($time, 2, 2).':00',
                'acq_date' => $r['acq_date'],
                'latitude' => $lat,
                'longitude' => $lon,
                'frp' => (float) $r['frp'],
                'confidence' => (string) $r['confidence'],
                'daynight' => $r['daynight'],
                'version' => (string) $r['version'],
            ];
        }

        $inserted = 0;

        foreach (array_chunk($rows, 1000) as $chunk) {
            $inserted += DB::table('detections')->insertOrIgnore($chunk);
        }

        $dates = array_column($rows, 'acq_date');

        return ['read' => $read, 'kept' => count($rows), 'inserted' => $inserted, 'from' => $dates ? min($dates) : null, 'to' => $dates ? max($dates) : null];
    }

    public static function satelliteName(string $code): string
    {
        return ['T' => 'Terra', 'A' => 'Aqua', 'N' => 'Suomi NPP', 'N20' => 'NOAA-20', 'N21' => 'NOAA-21'][$code] ?? $code;
    }

    public static function satellite(string $value): string
    {
        return match ($value) {
            'Terra', 'T' => 'T',
            'Aqua', 'A' => 'A',
            '1', 'N20', 'NOAA-20' => 'N20',
            '2', 'N21', 'NOAA-21' => 'N21',
            default => substr($value, 0, 4),
        };
    }
}
