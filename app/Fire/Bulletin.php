<?php

namespace App\Fire;

use Anthropic\Client;
use App\Models\Alert;
use App\Models\Area;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * A short, plain-language fire bulletin for an area and week. The code
 * computes every fact; a language model only writes the sentences, and
 * any number in its text that is not one of the facts sends the bulletin
 * back to a fixed template. Judges and users never see an invented figure.
 */
class Bulletin
{
    public function __construct(private Insights $insights) {}

    /** @return array{text: string, source: string, model: ?string, facts: array<string, mixed>} */
    public function for(Area $area, ?string $week, string $locale): array
    {
        $key = "bulletin:{$area->id}:".($week ?? 'latest').":{$locale}:".($area->processed_at?->timestamp ?? 0);

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $facts = $this->facts($area, $week, $locale);
        $written = $this->write($facts, $locale);

        if ($written !== null && self::onlyKnownNumbers($written['text'], $facts)) {
            $result = ['text' => $written['text'], 'source' => 'ai', 'model' => $written['model'], 'facts' => $facts];
            Cache::put($key, $result, now()->addDay());

            return $result;
        }

        // Keep the template only briefly, so the written version can replace it soon.
        $result = ['text' => self::template($facts, $locale), 'source' => 'template', 'model' => null, 'facts' => $facts];
        Cache::put($key, $result, now()->addMinutes(10));

        return $result;
    }

    /**
     * The cached bulletin, or the template, without waiting on the model.
     *
     * @return array{text: string, source: string, model: ?string, facts: array<string, mixed>}
     */
    public function ready(Area $area, ?string $week, string $locale): array
    {
        $key = "bulletin:{$area->id}:".($week ?? 'latest').":{$locale}:".($area->processed_at?->timestamp ?? 0);

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $facts = $this->facts($area, $week, $locale);

        return ['text' => self::template($facts, $locale), 'source' => 'template', 'model' => null, 'facts' => $facts];
    }

    /** @return array<string, mixed> */
    public function facts(Area $area, ?string $week, string $locale): array
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);
        $season = $this->insights->season($area);
        $month = fn (int $m) => CarbonImmutable::create(2020, $m, 1)->locale($locale)->translatedFormat('F');
        $facts = ['area' => $area->label(), 'language' => $locale === 'es' ? 'Spanish' : 'English', 'unit' => $locale === 'es' ? 'celdas-día de 1 km con fuego' : 'burning 1 km cell-days'];

        if ($season) {
            $facts['season'] = ['start' => $month($season['start']), 'peak' => $month($season['peak']), 'end' => $month($season['end']), 'share_of_year_percent' => (int) round($season['share'] * 100)];
        }

        $alert = $week ? Alert::query()->where('area_id', $area->id)->whereDate('week_start', $week)->first() : null;

        if ($alert) {
            $start = CarbonImmutable::parse($alert->week_start);
            $sameWeek = $this->sameWeekValues($area, $start);
            $facts['week'] = [
                'starts' => $start->locale($locale)->isoFormat($locale === 'es' ? 'D [de] MMMM [de] YYYY' : 'MMMM D, YYYY'),
                'burning_cell_days' => (int) round($alert->observed),
                'typical_for_this_week' => (int) round($alert->typical),
                'times_normal' => round($alert->observed / max($alert->typical, 1), 1),
                'rank_among_same_week' => $sameWeek['rank'],
                'years_compared' => $sameWeek['years'],
                'in_fire_season' => $season ? in_array($start->month, $season['season'], true) : null,
                'where' => $this->where($area, $start->toDateString(), $start->addDays(6)->toDateString(), $locale),
            ];
        } else {
            $to = CarbonImmutable::today();
            $from = $to->subDays(29);
            $cmp = $this->insights->compare($area, $from->toDateString(), $to->toDateString());
            $facts['last_30_days'] = [
                'window_days' => 30,
                'burning_cell_days' => (int) round($cmp['value'] ?? 0),
                'typical_for_these_dates' => (int) round($cmp['typical']),
                'times_normal' => $cmp['times'],
                'rank' => $cmp['rank'],
                'years_compared' => $cmp['of'],
                'in_fire_season' => $season['in_season'] ?? null,
                'where' => $this->where($area, $from->toDateString(), $to->toDateString(), $locale),
            ];
        }

        app()->setLocale($previous);

        return $facts;
    }

    /** Rank of a week among the same ISO week in every year (1 = most fire). */
    private function sameWeekValues(Area $area, CarbonImmutable $start): array
    {
        $weeks = DetectAlerts::weeks(app(Harmonizer::class)->daily($area, Sensors::MODIS));
        $same = array_filter($weeks, fn ($v, $s) => CarbonImmutable::parse($s)->isoWeek === $start->isoWeek, ARRAY_FILTER_USE_BOTH);
        $value = $same[$start->toDateString()] ?? 0;

        return ['rank' => 1 + count(array_filter($same, fn ($v) => $v > $value)), 'years' => count($same)];
    }

    /** Which part of the area held most of the burning cells: a 3×3 split of the box. */
    private function where(Area $area, string $from, string $to, string $locale): ?string
    {
        [$south, $north, $west, $east] = $area->cellBounds();
        $rows = DB::select(<<<'SQL'
            select least(2, floor((cell_lat - ?::int) * 3.0 / ?::int))::int as r, least(2, floor((cell_lon - ?::int) * 3.0 / ?::int))::int as c, count(*) as n
            from cell_days where acq_date between ? and ? and cell_lat >= ? and cell_lat < ? and cell_lon >= ? and cell_lon < ?
            group by 1, 2 order by n desc
            SQL, [$south, max(1, $north - $south), $west, max(1, $east - $west), $from, $to, $south, $north, $west, $east]);

        $total = array_sum(array_map(fn ($r) => $r->n, $rows));

        if ($total < 5) {
            return null;
        }

        $top = $rows[0];
        $names = $locale === 'es'
            ? [['el suroeste', 'el sur', 'el sureste'], ['el oeste', 'el centro', 'el este'], ['el noroeste', 'el norte', 'el noreste']]
            : [['the southwest', 'the south', 'the southeast'], ['the west', 'the centre', 'the east'], ['the northwest', 'the north', 'the northeast']];

        return $names[$top->r][$top->c];
    }

    /** @return array{text: string, model: string}|null */
    private function write(array $facts, string $locale): ?array
    {
        $system = 'You write a short fire bulletin for a fire agency, in '.$facts['language'].'. Write two or three plain sentences. '
            .'Use only the facts given, and copy numbers exactly as written in the facts. Do not add any other number, date, percentage or claim. '
            .'Say "times normal" with the number, mention where in the area the fire was if given, and whether it was inside the usual fire season. '
            .'Calm, factual tone: no exclamation marks, no advice, no hype, no headings, no lists.';

        return self::ask($system, $facts);
    }

    /**
     * One call to the writing model: a system prompt and the facts as JSON.
     * Claude (Anthropic API) or the self-hosted open model, whichever is configured first; the other is the fallback.
     * Null on any failure or an over-long answer.
     *
     * @param  array<string, mixed>  $facts
     * @return array{text: string, model: string}|null
     */
    public static function ask(string $system, array $facts, int $maxLength = 700): ?array
    {
        $order = config('services.bulletin.provider') === 'anthropic' ? ['anthropic', 'ollama'] : ['ollama', 'anthropic'];

        foreach ($order as $provider) {
            $answer = $provider === 'anthropic' ? self::askClaude($system, $facts) : self::askOllama($system, $facts);

            if ($answer !== null && $answer['text'] !== '' && mb_strlen($answer['text']) <= $maxLength) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array{text: string, model: string}|null
     */
    private static function askClaude(string $system, array $facts): ?array
    {
        $key = config('services.bulletin.anthropic_key');

        if (! $key) {
            return null;
        }

        $model = (string) config('services.bulletin.anthropic_model');

        try {
            $client = new Client(apiKey: $key, requestOptions: ['timeout' => (float) config('services.bulletin.timeout'), 'maxRetries' => 1]);
            $message = $client->messages->create(
                model: $model,
                maxTokens: 600,
                system: $system,
                outputConfig: ['effort' => 'low'],
                messages: [['role' => 'user', 'content' => json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]],
            );
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if ($message->stopReason === 'refusal') {
            return null;
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return ['text' => trim($text), 'model' => $model];
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array{text: string, model: string}|null
     */
    private static function askOllama(string $system, array $facts): ?array
    {
        $url = config('services.bulletin.url');

        if (! $url) {
            return null;
        }

        $model = (string) config('services.bulletin.model');

        try {
            $response = Http::timeout(config('services.bulletin.timeout'))
                ->withToken((string) config('services.bulletin.key'))
                ->post(rtrim($url, '/').'/api/chat', [
                    'model' => $model,
                    'stream' => false,
                    // Gemma 4 thinks before answering by default; the facts are already the reasoning, so answer directly.
                    'think' => false,
                    'options' => ['temperature' => 0.2, 'num_predict' => 320],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
                    ],
                ])->throw()->json();
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return ['text' => trim((string) ($response['message']['content'] ?? '')), 'model' => $model];
    }

    /** Every number written must be one of the facts' numbers (ignoring thousands separators and decimal style). */
    public static function onlyKnownNumbers(string $text, array $facts): bool
    {
        $known = [];
        array_walk_recursive($facts, function ($value) use (&$known) {
            if (is_int($value) || is_float($value)) {
                $known[] = self::norm((string) $value);
            } elseif (is_string($value)) {
                preg_match_all('/\d[\d.,]*/', $value, $m);
                foreach ($m[0] as $n) {
                    $known[] = self::norm($n);
                }
            }
        });

        preg_match_all('/\d[\d.,]*/', $text, $found);

        foreach ($found[0] as $n) {
            if (! in_array(self::norm($n), $known, true)) {
                return false;
            }
        }

        return true;
    }

    private static function norm(string $n): string
    {
        $n = rtrim($n, '.,');
        // "1,352" / "1.352" (thousands) → 1352; "11,5" / "11.5" (decimal) → 11.5
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $n)) {
            return str_replace([',', '.'], '', $n);
        }

        $n = str_replace(',', '.', $n);

        return rtrim(rtrim(str_contains($n, '.') ? $n : $n.'.', '0'), '.') ?: '0';
    }

    public static function template(array $facts, string $locale): string
    {
        $es = $locale === 'es';
        $fmt = fn ($n) => is_numeric($n) ? number_format((float) $n, floor((float) $n) == (float) $n ? 0 : 1, $es ? ',' : '.', $es ? '.' : ',') : $n;
        $w = isset($facts['week']) ? array_map($fmt, $facts['week']) : null;
        $l = isset($facts['last_30_days']) ? array_map($fmt, $facts['last_30_days']) : null;
        $season = $facts['season'] ?? null;

        if ($w) {
            $text = $es
                ? "La semana del {$w['starts']}, {$facts['area']} tuvo {$w['burning_cell_days']} celdas-día con fuego, {$w['times_normal']} veces lo normal para esa semana ({$w['typical_for_this_week']}). Es la número {$w['rank_among_same_week']} de {$w['years_compared']} años."
                : "In the week of {$w['starts']}, {$facts['area']} had {$w['burning_cell_days']} burning cell-days, {$w['times_normal']} times normal for that week ({$w['typical_for_this_week']}). It ranks {$w['rank_among_same_week']} of {$w['years_compared']} years.";
            $where = $w['where'] ?? null;
            $in = $w['in_fire_season'] ?? null;
        } else {
            $text = $es
                ? "En los últimos 30 días, {$facts['area']} tuvo {$l['burning_cell_days']} celdas-día con fuego, frente a {$l['typical_for_these_dates']} típicas en las mismas fechas."
                : "In the last 30 days, {$facts['area']} had {$l['burning_cell_days']} burning cell-days, against a typical {$l['typical_for_these_dates']} for the same dates.";
            $where = $l['where'] ?? null;
            $in = $l['in_fire_season'] ?? null;
        }

        if ($where) {
            $text .= $es ? " La mayor parte del fuego estuvo en {$where}." : " Most of the fire was in {$where}.";
        }

        if ($season && $in !== null) {
            $text .= $es
                ? ($in ? " Fue dentro de la temporada habitual ({$season['start']} a {$season['end']})." : " Fue fuera de la temporada habitual ({$season['start']} a {$season['end']}).")
                : ($in ? " It was inside the usual fire season ({$season['start']} to {$season['end']})." : " It was outside the usual fire season ({$season['start']} to {$season['end']}).");
        }

        return $text;
    }
}
