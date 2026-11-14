<?php

namespace App\Fire;

use Carbon\CarbonImmutable;

/**
 * A date range against the same calendar dates in every other year, on the
 * harmonized MODIS scale. Only years with data on every day of the range count.
 */
class CompareDates
{
    public const MAX_DAYS = 366;

    /**
     * @param  list<array{date: string, value: ?float}>  $daily
     * @return array<string, mixed>
     */
    public static function run(array $daily, string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from);
        $end = CarbonImmutable::parse($to);
        $length = (int) $start->diffInDays($end) + 1;
        $values = [];

        foreach ($daily as $day) {
            $values[$day['date']] = $day['value'];
        }

        $years = [];
        $first = (int) substr((string) array_key_first($values), 0, 4);
        $last = (int) substr((string) array_key_last($values), 0, 4);

        for ($year = $first; $year <= $last; $year++) {
            $offset = $year - $start->year;
            $windowStart = $start->addYears($offset);
            $sum = 0.0;
            $complete = true;

            for ($d = 0; $d < $length; $d++) {
                $value = $values[$windowStart->addDays($d)->toDateString()] ?? null;

                if ($value === null) {
                    $complete = false;
                    break;
                }

                $sum += $value;
            }

            if ($complete) {
                $years[] = ['year' => $year, 'from' => $windowStart->toDateString(), 'value' => round($sum, 1)];
            }
        }

        $selected = collect($years)->firstWhere('from', $start->toDateString());
        $others = array_values(array_filter($years, fn ($y) => $y['from'] !== $start->toDateString()));
        $typical = Stats::median(array_column($others, 'value'));
        $rank = $selected ? 1 + count(array_filter($years, fn ($y) => $y['value'] > $selected['value'])) : null;

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'days' => $length,
            'value' => $selected['value'] ?? null,
            'typical' => round($typical, 1),
            'times' => $selected && $typical > 0 ? round($selected['value'] / $typical, 1) : null,
            'rank' => $rank,
            'of' => count($years),
            'years' => $years,
        ];
    }
}
