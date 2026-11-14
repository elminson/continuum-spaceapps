<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['week_start' => 'date', 'observed' => 'float', 'typical' => 'float', 'spread' => 'float', 'score' => 'float'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
