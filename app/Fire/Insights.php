<?php

namespace App\Fire;

use App\Models\Alert;
use App\Models\Area;
use App\Models\AreaFit;
use Illuminate\Support\Facades\Cache;

/** Cached read models for the pages and the API. */
class Insights
{
    public function __construct(private Harmonizer $harmonizer, private RetirementTest $retirement) {}

    /** @return list<array<string, mixed>> */
    public function monthly(Area $area, string $scale): array
    {
        return Cache::remember($this->key($area, "monthly:{$scale}"), now()->addDay(), fn () => $this->harmonizer->monthly($area, $scale));
    }

    /** @return array<string, mixed>|null */
    public function fit(Area $area): ?array
    {
        $fits = AreaFit::query()->where('area_id', $area->id)->orderBy('month')->get();
        $all = $fits->firstWhere('month', 0);

        if (! $all) {
            return null;
        }

        return [
            'ratio' => round($all->ratio, 4),
            'low' => round($all->ratio_low, 4),
            'high' => round($all->ratio_high, 4),
            'days' => $all->days,
            'validation' => $all->validation,
            'months' => $fits->where('month', '>', 0)->map(fn (AreaFit $f) => ['month' => $f->month, 'ratio' => round($f->ratio, 4), 'low' => round($f->ratio_low, 4), 'high' => round($f->ratio_high, 4), 'days' => $f->days])->values()->all(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function alerts(Area $area, int $limit = 12): array
    {
        return Alert::query()->where('area_id', $area->id)->orderByDesc('score')->limit($limit)->get()
            ->map(fn (Alert $a) => self::alert($a))->all();
    }

    /** @return array<string, mixed> */
    public static function alert(Alert $a): array
    {
        return [
            'week_start' => $a->week_start->toDateString(),
            'observed' => $a->observed,
            'typical' => $a->typical,
            'times' => $a->typical > 0 ? round($a->observed / $a->typical, 1) : null,
            'score' => $a->score,
            'severity' => $a->severity,
        ];
    }

    /** @return array<string, mixed> */
    public function retirement(Area $area, ?int $year): array
    {
        return Cache::remember($this->key($area, 'retirement:'.($year ?? 'default')), now()->addDay(), fn () => $this->retirement->run($area, $year));
    }

    /** Small summary for the area cards. */
    public function card(Area $area): array
    {
        $monthly = $this->monthly($area, Sensors::MODIS);
        $last = array_slice($monthly, -24);
        $latestAlert = Alert::query()->where('area_id', $area->id)->orderByDesc('week_start')->first();

        return [
            'slug' => $area->slug,
            'name' => $area->label(),
            'first_month' => $monthly[0]['month'] ?? null,
            'last_month' => end($monthly)['month'] ?? null,
            'spark' => array_map(fn ($m) => $m['value'], $last),
            'improvement' => $this->fit($area)['validation']['improvement'] ?? null,
            'latest_alert' => $latestAlert ? self::alert($latestAlert) : null,
        ];
    }

    private function key(Area $area, string $what): string
    {
        return "insights:{$area->id}:{$what}:".($area->processed_at?->timestamp ?? 0);
    }
}
