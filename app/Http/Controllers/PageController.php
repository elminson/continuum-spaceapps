<?php

namespace App\Http\Controllers;

use App\Fire\DataStatus;
use App\Fire\Explorer;
use App\Fire\FirmsFeed;
use App\Fire\Insights;
use App\Fire\Sensors;
use App\Http\Middleware\SetLocale;
use App\Models\Alert;
use App\Models\Area;
use App\Models\SavedBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(Insights $insights, DataStatus $status): Response
    {
        return Inertia::render('Home', [
            'areas' => Area::query()->orderBy('id')->get()->map(fn (Area $area) => $insights->card($area))->all(),
            'status' => $status->summary(),
            'places' => Explorer::places(),
            'topAlerts' => $this->topAlerts(5),
        ]);
    }

    public function areas(Insights $insights): Response
    {
        return Inertia::render('Areas', [
            'areas' => Area::query()->orderBy('id')->get()->map(fn (Area $area) => [...$insights->card($area), 'bbox' => [$area->west, $area->south, $area->east, $area->north], 'fit' => $insights->fit($area)])->all(),
        ]);
    }

    public function alerts(Request $request): Response
    {
        $severities = ['extreme', 'high', 'elevated'];
        $severity = in_array($request->query('severity'), $severities, true) ? $request->query('severity') : null;
        $sort = $request->query('sort') === 'strongest' ? 'strongest' : 'recent';
        $areas = Area::query()->orderBy('id')->get();
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
        ]);
    }

    public function data(DataStatus $status): Response
    {
        return Inertia::render('Data', [
            'status' => $status->summary(),
            'freshness' => $status->freshness(),
            'feeds' => FirmsFeed::FEEDS,
        ]);
    }

    public function howItWorks(DataStatus $status, Insights $insights): Response
    {
        return Inertia::render('HowItWorks', [
            'status' => $status->summary(),
            'evidence' => Area::query()->orderBy('id')->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'fit' => $insights->fit($a)])->all(),
            'hispaniola' => Area::query()->where('slug', 'hispaniola')->first(['west', 'south', 'east', 'north'])?->only('west', 'south', 'east', 'north'),
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
            'week' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('week')) ? $request->query('week') : null,
        ]);
    }

    public function explore(Request $request): Response
    {
        return Inertia::render('Explore', [
            'areas' => Area::query()->orderBy('id')->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'bbox' => [$a->west, $a->south, $a->east, $a->north]])->all(),
            'places' => Explorer::places(),
            'open' => ['place' => $request->query('place'), 'week' => $request->query('week')],
            'boxes' => $request->user()?->savedBoxes()->latest()->get()->map(fn (SavedBox $b) => $b->toPlace())->all() ?? [],
        ]);
    }

    public function method(): Response
    {
        return Inertia::render('Method');
    }

    public function locale(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}
