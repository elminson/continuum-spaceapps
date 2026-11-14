<?php

namespace App\Http\Controllers;

use App\Fire\Insights;
use App\Fire\Sensors;
use App\Http\Middleware\SetLocale;
use App\Models\Alert;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(Insights $insights): Response
    {
        return Inertia::render('Home', [
            'areas' => Area::query()->orderBy('id')->get()->map(fn (Area $area) => $insights->card($area))->all(),
            'detections' => cache()->remember('detections:count', now()->addHour(), fn () => (int) DB::table('detections')->count()),
            'topAlerts' => Alert::query()->with('area')->orderByDesc('score')->limit(5)->get()
                ->map(fn (Alert $a) => [...Insights::alert($a), 'area' => $a->area->label(), 'slug' => $a->area->slug])->all(),
        ]);
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

    public function explore(): Response
    {
        return Inertia::render('Explore', [
            'areas' => Area::query()->orderBy('id')->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'bbox' => [$a->west, $a->south, $a->east, $a->north]])->all(),
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
