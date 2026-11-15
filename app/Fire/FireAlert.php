<?php

namespace App\Fire;

use App\Models\AlertSubscription;
use Carbon\CarbonImmutable;

/**
 * Decides which unusual week a subscription should hear about, and writes the alert text
 * from the computed numbers with a fixed template (no model involved).
 */
class FireAlert
{
    /** A week flagged in the last two weeks counts as current. */
    public const RECENT_DAYS = 14;

    public function __construct(private Explorer $explorer) {}

    /**
     * The latest flagged week of the box, or null when the last two weeks were normal.
     *
     * @return array<string, mixed>|null
     */
    public function current(AlertSubscription $s): ?array
    {
        $alerts = $this->alerts($s);
        $since = CarbonImmutable::today()->subDays(self::RECENT_DAYS)->toDateString();
        $recent = array_filter($alerts, fn ($a) => $a['week_start'] >= $since);
        usort($recent, fn ($a, $b) => strcmp($b['week_start'], $a['week_start']));

        return $recent[0] ?? null;
    }

    /**
     * The strongest flagged week ever, for the test alert.
     *
     * @return array<string, mixed>|null
     */
    public function strongest(AlertSubscription $s): ?array
    {
        return $this->alerts($s)[0] ?? null;
    }

    /** @return list<array<string, mixed>> */
    private function alerts(AlertSubscription $s): array
    {
        $result = $this->explorer->analyze($s->bbox());

        return $result['available'] ? $result['alerts'] : [];
    }

    /**
     * The alert in plain words, like the area bulletins but from a fixed template.
     *
     * @param  array<string, mixed>  $alert
     */
    public static function text(AlertSubscription $s, array $alert): string
    {
        $es = $s->locale === 'es';
        $fmt = fn (float|int $n, int $d = 0) => number_format($n, $d, $es ? ',' : '.', $es ? '.' : ',');
        $week = CarbonImmutable::parse($alert['week_start']);
        $date = $es ? $week->locale('es')->translatedFormat('j \d\e F \d\e Y') : $week->format('F j, Y');
        $ratio = $alert['times'] ?? ($alert['typical'] > 0 ? round($alert['observed'] / $alert['typical'], 1) : null);
        $times = $ratio !== null ? $fmt($ratio, 1) : null;

        if ($es) {
            $text = "La semana del {$date}, {$s->name} tuvo {$fmt($alert['observed'])} celdas-día de 1 km con fuego";
            $text .= $times ? ", {$times} veces lo normal para esa semana ({$fmt($alert['typical'])})." : '.';
            $text .= ' Continuum marca una semana cuando supera en al menos 3 desviaciones la mediana de la misma semana en todos los demás años y al menos la duplica.';

            return $text;
        }

        $text = "In the week of {$date}, {$s->name} had {$fmt($alert['observed'])} burning 1 km cell-days";
        $text .= $times ? ", {$times} times normal for that week ({$fmt($alert['typical'])})." : '.';
        $text .= ' Continuum flags a week when it is at least 3 deviations above the median of the same week in every other year, and at least twice it.';

        return $text;
    }
}
