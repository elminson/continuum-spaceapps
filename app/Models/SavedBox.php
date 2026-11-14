<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedBox extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['west' => 'float', 'south' => 'float', 'east' => 'float', 'north' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{id: int, name: string, bbox: array{0: float, 1: float, 2: float, 3: float}} */
    public function toPlace(): array
    {
        return ['id' => $this->id, 'slug' => "box-{$this->id}", 'name' => $this->name, 'bbox' => [$this->west, $this->south, $this->east, $this->north]];
    }
}
