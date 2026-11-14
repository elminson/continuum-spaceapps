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

    public function alerts(): Response
    {
        return Inertia::render('Alerts', [
            'strongest' => $this->topAlerts(25),
            'recent' => Alert::query()->with('area')->orderByDesc('week_start')->limit(25)->get()
                ->map(fn (Alert $a) => [...Insights::alert($a), 'area' => $a->area->label(), 'slug' => $a->area->slug])->all(),
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

    public function howItWorks(DataStatus $status): Response
    {
        return Inertia::render('HowItWorks', ['status' => $status->summary()]);
    }

    public function apiDocs(): Response
    {
        return Inertia::render('ApiDocs');
    }

    /** @return list<array<string, mixed>> */
    private function topAlerts(int $limit): array
    {
        return Alert::query()->with('area')->orderByDesc('score')->limit($limit)->get()
            ->map(fn (Alert $a) => [...Insights::alert($a), 'area' => $a->area->label(), 'slug' => $a->area->slug])->all();
    }

    public function area(Request $request, Area $area, Insights $insights): Response
    {
        $scale = $request->query('scale') === Sensors::VIIRS ? Sensors::VIIRS : Sensors::MODIS;

        return Inertia::render('Area', [
            'area' => ['slug' => $area->slug, 'name' => $area->label(), 'bbox' => [$area->west, $area->south, $area->east, $area->north], 'processed_at' => $area->processed_at?->toIso8601String()],
            'scale' => $scale,
            'monthly' => $insights->monthly($area, $scale),
            'fit' => $insights->fit($area),
            'alerts' => $insights->alerts($area),
        ]);
    }

    public function explore(Request $request): Response
    {
        return Inertia::render('Explore', [
            'areas' => Area::query()->orderBy('id')->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'bbox' => [$a->west, $a->south, $a->east, $a->north]])->all(),
            'places' => Explorer::places(),
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
