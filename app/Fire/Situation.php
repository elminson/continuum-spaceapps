<?php

namespace App\Fire;

use App\Models\Alert;
use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * A reading of the current situation across all areas, written by the model from computed facts only:
 * the statistics decide which weeks are unusual; the model says which areas deserve attention and why.
 */
class Situation
{
    public const WINDOW_DAYS = 60;

    public function __construct(private Insights $insights, private Bulletin $bulletin) {}

    /** @return array{text: string, source: string, model: ?string, facts: array<string, mixed>} */
    public function for(string $locale): array
    {
        $key = $this->key($locale);

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $facts = $this->facts($locale);
        $written = Bulletin::ask(self::prompt($facts), $facts);

        if ($written !== null && Bulletin::onlyKnownNumbers($written, $facts)) {
            $result = ['text' => $written, 'source' => 'ai', 'model' => config('services.bulletin.model'), 'facts' => $facts];
            Cache::put($key, $result, now()->addHours(6));

            return $result;
        }

        $result = ['text' => self::template($facts, $locale), 'source' => 'template', 'model' => null, 'facts' => $facts];
        Cache::put($key, $result, now()->addMinutes(10));

        return $result;
    }

    /** The cached reading, or the template, without waiting on the model. */
    public function ready(string $locale): array
    {
        if ($cached = Cache::get($this->key($locale))) {
            return $cached;
        }

        $facts = $this->facts($locale);

        return ['text' => self::template($facts, $locale), 'source' => 'template', 'model' => null, 'facts' => $facts];
    }

    /** @return array<string, mixed> */
    public function facts(string $locale): array
    {
        $since = CarbonImmutable::today()->subDays(self::WINDOW_DAYS);
        $areas = [];

        foreach (Area::query()->ordered()->get() as $area) {
            $base = $this->bulletin->facts($area, null, $locale);
            $weeks = Alert::query()->where('area_id', $area->id)->whereDate('week_start', '>=', $since)->orderByDesc('week_start')->get()
                ->map(fn (Alert $a) => [
                    'starts' => $a->week_start->locale($locale)->isoFormat($locale === 'es' ? 'D [de] MMMM' : 'MMMM D'),
                    'times_normal' => $a->typical > 0 ? round($a->observed / $a->typical, 1) : null,
                    'level' => $a->severity,
                    'in_fire_season' => isset($base['season']) ? ($this->insights->season($area)['season'] ?? null ? in_array($a->week_start->month, $this->insights->season($area)['season'], true) : null) : null,
                ])->all();

            $areas[] = [
                'area' => $area->label(),
                'season' => $base['season'] ?? null,
                'last_30_days' => $base['last_30_days'] ?? null,
                'unusual_weeks_last_60_days' => $weeks,
            ];
        }

        return [
            'language' => $locale === 'es' ? 'Spanish' : 'English',
            'today' => CarbonImmutable::today()->locale($locale)->isoFormat($locale === 'es' ? 'D [de] MMMM [de] YYYY' : 'MMMM D, YYYY'),
            'unit' => $locale === 'es' ? 'celdas-día de 1 km con fuego' : 'burning 1 km cell-days',
            'how_a_week_is_flagged' => 'at least 3 deviations above the median of the same week in every other year since 2000, and at least twice it',
            'areas' => $areas,
        ];
    }

    /** @param array<string, mixed> $facts */
    public static function prompt(array $facts): string
    {
        return 'You are a fire analyst writing a short situation reading for fire managers, in '.$facts['language'].'. '
            .'Write three to five plain sentences: which areas deserve attention now and why, whether their unusual weeks fall inside or outside the usual fire season, and which areas are quiet. '
            .'Use only the facts given and copy numbers exactly as written; do not add any other number, date, percentage or claim. '
            .'The statistics already decided which weeks are unusual; you only explain. '
            .'Calm, factual tone: no exclamation marks, no advice beyond watching an area, no headings, no lists.';
    }

    /** @param array<string, mixed> $facts */
    public static function template(array $facts, string $locale): string
    {
        $es = $locale === 'es';
        $fmt = fn ($n) => number_format((float) $n, floor((float) $n) == (float) $n ? 0 : 1, $es ? ',' : '.', $es ? '.' : ',');
        $flagged = [];
        $inSeason = [];
        $quiet = [];

        foreach ($facts['areas'] as $a) {
            foreach ($a['unusual_weeks_last_60_days'] as $w) {
                $flagged[] = ['area' => $a['area'], 'times' => $w['times_normal'] ?? 0, 'starts' => $w['starts']];
            }
            if ($a['last_30_days']['in_fire_season'] ?? false) {
                $inSeason[] = $a['area'];
            }
            if ($a['unusual_weeks_last_60_days'] === []) {
                $quiet[] = $a['area'];
            }
        }

        usort($flagged, fn ($x, $y) => $y['times'] <=> $x['times']);
        $areasFlagged = count(array_unique(array_column($flagged, 'area')));
        $top = $flagged[0] ?? null;

        if ($es) {
            $text = $flagged === []
                ? 'En los últimos 60 días ninguna semana fue inusual en las áreas seguidas.'
                : sprintf('En los últimos 60 días hubo %s semana(s) inusual(es) en %s área(s). La más fuerte: %s, semana del %s, %s veces lo normal.', $fmt(count($flagged)), $fmt($areasFlagged), $top['area'], $top['starts'], $fmt($top['times']));
            $text .= $inSeason ? ' En temporada de fuego ahora: '.implode(', ', $inSeason).'.' : ' Ninguna área está en su temporada de fuego ahora.';
            $text .= $quiet ? ' Sin semanas inusuales: '.implode(', ', $quiet).'.' : '';

            return $text;
        }

        $text = $flagged === []
            ? 'In the last 60 days no week was unusual in the areas followed.'
            : sprintf('In the last 60 days there were %s unusual week(s) across %s area(s). The strongest: %s, week of %s, %s times normal.', $fmt(count($flagged)), $fmt($areasFlagged), $top['area'], $top['starts'], $fmt($top['times']));
        $text .= $inSeason ? ' In fire season now: '.implode(', ', $inSeason).'.' : ' No area is in its fire season now.';
        $text .= $quiet ? ' No unusual weeks: '.implode(', ', $quiet).'.' : '';

        return $text;
    }

    private function key(string $locale): string
    {
        return "situation:{$locale}:".(Area::query()->max('processed_at') ?? 0);
    }
}
