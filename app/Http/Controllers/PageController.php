<?php

namespace App\Http\Controllers;

use App\Fire\Bulletin;
use App\Fire\Calibrate;
use App\Fire\DailyRecord;
use App\Fire\DataStatus;
use App\Fire\Explorer;
use App\Fire\FirmsFeed;
use App\Fire\Insights;
use App\Fire\Sensors;
use App\Fire\Situation;
use App\Http\Controllers\Api\AreaApiController;
use App\Http\Middleware\SetLocale;
use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\Area;
use App\Models\AreaFit;
use App\Models\IngestRun;
use App\Models\SavedBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(Insights $insights, DataStatus $status): Response
    {
        return Inertia::render('Home', [
            'areas' => Area::query()->ordered()->get()->map(fn (Area $area) => $insights->card($area))->all(),
            'status' => $status->summary(),
            'places' => Explorer::places(),
            'topAlerts' => $this->topAlerts(5),
            'answers' => $this->answers($insights),
        ]);
    }

    /**
     * Live facts behind the "Questions Continuum answers" block on the home page.
     *
     * @return array<string, mixed>|null
     */
    private function answers(Insights $insights): ?array
    {
        $dr = Area::query()->where('slug', Area::FOCUS)->first();
        $cuba = Area::query()->where('slug', 'cuba')->first();

        if (! $dr) {
            return null;
        }

        $top = $dr->alerts()->orderByDesc('score')->first();
        $fit = $insights->fit($dr);
        $season = $insights->season($dr);
        $retire = $insights->retirement($dr, null);
        $cubaCompare = $cuba ? $insights->compare($cuba, '2026-08-10', '2026-08-31') : null;

        return [
            'week' => $top ? Insights::alert($top) : null,
            'season' => $season ? ['start' => $season['start'], 'peak' => $season['peak'], 'end' => $season['end'], 'share' => $season['share'], 'in_season' => $season['in_season']] : null,
            'compare' => $cubaCompare ? ['from' => $cubaCompare['from'], 'to' => $cubaCompare['to'], 'times' => $cubaCompare['times'], 'rank' => $cubaCompare['rank'], 'of' => $cubaCompare['of']] : null,
            'trust' => $fit['validation'] ?? null,
            'retire' => $retire && ($retire['available'] ?? false) ? ['year' => $retire['year'], 'improvement' => $retire['improvement'], 'monthly_r' => $retire['monthly_r']] : null,
        ];
    }

    public function areas(Request $request, Insights $insights): Response
    {
        $user = $request->user();

        return Inertia::render('Areas', [
            'areas' => Area::query()->ordered()->get()->map(fn (Area $area) => [...$insights->card($area), 'bbox' => [$area->west, $area->south, $area->east, $area->north], 'fit' => $insights->fit($area)])->all(),
            'mine' => $user?->savedBoxes()->latest()->get()->map(fn (SavedBox $b) => $b->toPlace())->all() ?? [],
            'zones' => $user ? AlertSubscription::query()->where('user_id', $user->id)->latest()->get()->map->toArray()->all() : [],
        ]);
    }

    public function alerts(Request $request, Situation $situation): Response
    {
        $severities = ['extreme', 'high', 'elevated'];
        $severity = in_array($request->query('severity'), $severities, true) ? $request->query('severity') : null;
        $sort = $request->query('sort') === 'strongest' ? 'strongest' : 'recent';
        $areas = Area::query()->ordered()->get();
        $places = Explorer::places();
        $selected = (string) $request->query('area', '');

        // Every area and ready-made place, with its flagged weeks.
        $sources = [];

        foreach ($areas as $area) {
            $sources[$area->slug] = [
                'slug' => $area->slug, 'name' => $area->label(), 'bbox' => [$area->west, $area->south, $area->east, $area->north], 'kind' => 'area',
                'alerts' => $area->alerts()->get()->map(fn (Alert $a) => Insights::alert($a))->all(),
            ];
        }

        foreach ($places as $place) {
            $sources[$place['slug']] = [
                'slug' => $place['slug'], 'name' => $place['name'], 'bbox' => $place['bbox'], 'kind' => 'place',
                'alerts' => Explorer::cachedAlerts($place['slug']) ?? [],
            ];
        }

        $selected = isset($sources[$selected]) ? $selected : '';
        $list = collect($selected ? [$sources[$selected]] : array_filter($sources, fn ($s) => $s['kind'] === 'area'))
            ->flatMap(fn ($s) => array_map(fn ($a) => [
                ...$a, 'area' => $s['name'], 'slug' => $s['slug'],
                'href' => $s['kind'] === 'area' ? "/areas/{$s['slug']}?week={$a['week_start']}" : "/explore?place={$s['slug']}&week={$a['week_start']}",
            ], $s['alerts']))
            ->when($severity, fn ($c) => $c->where('severity', $severity))
            ->sortByDesc($sort === 'strongest' ? 'score' : 'week_start')
            ->values();

        $perPage = 15;
        $pages = max(1, (int) ceil($list->count() / $perPage));
        $page = min($pages, max(1, $request->integer('page', 1)));

        return Inertia::render('Alerts', [
            'filters' => ['area' => $selected, 'severity' => $severity, 'sort' => $sort],
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            'sources' => array_values(array_map(fn ($s) => [
                'slug' => $s['slug'], 'name' => $s['name'], 'bbox' => $s['bbox'], 'kind' => $s['kind'],
                'counts' => array_combine($severities, array_map(fn ($sev) => count(array_filter($s['alerts'], fn ($a) => $a['severity'] === $sev)), $severities)),
            ], $sources)),
            'total' => $list->count(),
            'alerts' => $list->forPage($page, $perPage)->values()->all(),
            'situation' => $situation->ready(app()->getLocale()),
        ]);
    }

    public function data(Request $request, DataStatus $status): Response
    {
        $feed = in_array($request->query('feed'), array_keys(FirmsFeed::FEEDS), true) ? $request->query('feed') : '';
        $state = in_array($request->query('status'), [IngestRun::DONE, IngestRun::FAILED], true) ? $request->query('status') : '';

        $runs = IngestRun::query()
            ->when($feed, fn ($q) => $q->where('feed', $feed))
            ->when($state, fn ($q) => $q->where('status', $state))
            ->orderByDesc('started_at');

        $perPage = 25;
        $total = (clone $runs)->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($pages, max(1, $request->integer('page', 1)));

        return Inertia::render('Data', [
            'status' => $status->summary(),
            'freshness' => $status->freshness(),
            'feeds' => FirmsFeed::FEEDS,
            'weather' => Area::query()->ordered()->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'days' => DB::table('weather_days')->where('area_id', $a->id)->count(), 'through' => DB::table('weather_days')->where('area_id', $a->id)->whereNotNull('t2m')->max('day')])->all(),
            'filters' => ['feed' => $feed, 'status' => $state],
            'totals' => cache()->remember('ingest:totals', now()->addMinute(), fn () => [
                'runs' => IngestRun::query()->where('status', IngestRun::DONE)->count(),
                'failed' => IngestRun::query()->where('status', IngestRun::FAILED)->count(),
                'inserted' => (int) IngestRun::query()->sum('inserted'),
                'since' => IngestRun::query()->min('started_at'),
            ]),
            'runs' => $runs->forPage($page, $perPage)->get()->map(fn (IngestRun $r) => $r->row())->all(),
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    public function howItWorks(DataStatus $status, Insights $insights, DailyRecord $record, Bulletin $bulletin): Response
    {
        $focus = Area::query()->where('slug', Area::FOCUS)->first();
        $top = $focus?->alerts()->orderByDesc('score')->first();

        return Inertia::render('HowItWorks', [
            'status' => $status->summary(),
            'evidence' => Area::query()->ordered()->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'fit' => $insights->fit($a)])->all(),
            'hispaniola' => $focus?->only('west', 'south', 'east', 'north'),
            'focusName' => $focus?->label(),
            'pairs' => $focus ? cache()->remember('how:pairs:'.$focus->id.':'.($focus->processed_at?->timestamp ?? 0), now()->addDay(), fn () => array_map(
                fn ($p) => [$p['viirs'], $p['modis']],
                Calibrate::overlap($record->for($focus)),
            )) : [],
            'counts' => [
                'areas' => Area::query()->count(),
                'fits' => AreaFit::query()->count(),
                'alerts' => Alert::query()->count(),
                'places' => count(Explorer::PLACES),
                'feeds' => count(FirmsFeed::FEEDS),
            ],
            'bulletin' => $focus && $top ? $bulletin->ready($focus, $top->week_start->toDateString(), app()->getLocale()) : null,
        ]);
    }

    public function judges(DataStatus $status, Insights $insights): Response
    {
        $focus = Area::query()->where('slug', Area::FOCUS)->first();
        $top = $focus?->alerts()->orderByDesc('score')->first();

        return Inertia::render('Judges', [
            'status' => $status->summary(),
            'focus' => $focus ? ['slug' => $focus->slug, 'name' => $focus->label(), 'fit' => $insights->fit($focus), 'top' => $top ? Insights::alert($top) : null] : null,
            'counts' => ['areas' => Area::query()->count(), 'alerts' => Alert::query()->count(), 'places' => count(Explorer::PLACES)],
        ]);
    }

    public function ai(Situation $situation, Bulletin $bulletin): Response
    {
        $locale = app()->getLocale();
        $provider = config('services.bulletin.provider') === 'anthropic' ? 'anthropic' : 'ollama';
        $names = ['anthropic' => 'Claude ('.config('services.bulletin.anthropic_model').')', 'ollama' => 'Gemma 4 ('.config('services.bulletin.model').', self-hosted)'];

        return Inertia::render('Ai', [
            'situation' => $situation->ready($locale),
            'areas' => Area::query()->ordered()->get()->map(function (Area $area) use ($bulletin, $locale) {
                $top = $area->alerts()->orderByDesc('score')->first();

                return [
                    'slug' => $area->slug,
                    'name' => $area->label(),
                    'top_week' => $top?->week_start->toDateString(),
                    'top_times' => $top && $top->typical > 0 ? round($top->observed / $top->typical, 1) : null,
                    'top' => $top ? $bulletin->ready($area, $top->week_start->toDateString(), $locale) : null,
                    'latest' => $bulletin->ready($area, null, $locale),
                ];
            })->all(),
            'writers' => ['primary' => $names[$provider], 'fallback' => $names[$provider === 'anthropic' ? 'ollama' : 'anthropic']],
        ]);
    }

    public function apiDocs(): Response
    {
        return Inertia::render('ApiDocs');
    }

    /** @return list<array<string, mixed>> */
    private function topAlerts(int $limit): array
    {
        return Alert::query()->with('area')->orderByDesc('score')->limit($limit)->get()
            ->map(fn (Alert $a) => [...Insights::alert($a), 'area' => $a->area->label(), 'slug' => $a->area->slug, 'href' => "/areas/{$a->area->slug}?week={$a->week_start->toDateString()}"])->all();
    }

    public function area(Request $request, Area $area, Insights $insights): Response
    {
        $scale = $request->query('scale') === Sensors::VIIRS ? Sensors::VIIRS : Sensors::MODIS;

        return Inertia::render('Area', [
            'area' => ['slug' => $area->slug, 'name' => $area->label(), 'bbox' => [$area->west, $area->south, $area->east, $area->north], 'processed_at' => $area->processed_at?->toIso8601String()],
            'scale' => $scale,
            'monthly' => $insights->monthly($area, $scale),
            'fit' => $insights->fit($area),
            'alerts' => $insights->alerts($area, 500),
            'season' => $insights->season($area),
            'forecast' => $insights->forecastReady($area),
            'week' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('week')) ? $request->query('week') : null,
        ]);
    }

    public function explore(Request $request): Response
    {
        return Inertia::render('Explore', [
            'areas' => Area::query()->ordered()->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'bbox' => [$a->west, $a->south, $a->east, $a->north]])->all(),
            'places' => Explorer::places(),
            'open' => ['place' => $request->query('place'), 'week' => $request->query('week'), 'bbox' => AreaApiController::bbox($request)],
            'boxes' => $request->user()?->savedBoxes()->latest()->get()->map(fn (SavedBox $b) => $b->toPlace())->all() ?? [],
        ]);
    }

    public function method(DataStatus $status, Insights $insights): Response
    {
        return Inertia::render('Method', [
            'status' => $status->summary(),
            'evidence' => Area::query()->ordered()->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'fit' => $insights->fit($a)])->all(),
            'example' => $this->ratioExample(),
            'forecasts' => Area::query()->ordered()->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'forecast' => $insights->forecastReady($a)])->all(),
        ]);
    }

    /** Σ MODIS and Σ VIIRS cell-days on the overlap days of the focus area, for the worked example. */
    private function ratioExample(): ?array
    {
        $area = Area::query()->where('slug', Area::FOCUS)->first();

        if (! $area) {
            return null;
        }

        return cache()->remember("ratio-example:{$area->id}:".($area->processed_at?->timestamp ?? 0), now()->addDay(), function () use ($area) {
            $row = DB::selectOne(<<<'SQL'
                select sum(case when instrument = 'modis' then cell_days else 0 end) as modis,
                       sum(case when instrument = 'viirs' then cell_days else 0 end) as viirs,
                       count(distinct acq_date) as days
                from area_days d
                where area_id = ? and acq_date >= ?
                  and exists (select 1 from coverage_days c where c.acq_date = d.acq_date and c.instrument = 'modis')
                  and exists (select 1 from coverage_days c where c.acq_date = d.acq_date and c.instrument = 'viirs')
                SQL, [$area->id, Sensors::VIIRS_START]);

            return ['area' => $area->label(), 'modis' => (int) $row->modis, 'viirs' => (int) $row->viirs, 'days' => (int) $row->days];
        });
    }

    public function locale(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}
