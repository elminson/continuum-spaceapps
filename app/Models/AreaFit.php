<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaFit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['ratio' => 'float', 'ratio_low' => 'float', 'ratio_high' => 'float', 'validation' => 'array'];
    }
}
