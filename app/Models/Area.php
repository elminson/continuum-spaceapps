<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'west' => 'float',
            'south' => 'float',
            'east' => 'float',
            'north' => 'float',
            'processed_at' => 'datetime',
        ];
    }

    public const FOCUS = 'dominican-republic';

    /** The Dominican Republic first, then the rest in the order they were added. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('case when slug = ? then 0 else 1 end', [self::FOCUS])->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fits(): HasMany
    {
        return $this->hasMany(AreaFit::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function label(): string
    {
        return app()->getLocale() === 'es' ? $this->name_es : $this->name;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} grid cell bounds: south, north, west, east (north/east exclusive)
     */
    public function cellBounds(): array
    {
        return [
            (int) floor($this->south * 100),
            (int) ceil($this->north * 100),
            (int) floor($this->west * 100),
            (int) ceil($this->east * 100),
        ];
    }
}
