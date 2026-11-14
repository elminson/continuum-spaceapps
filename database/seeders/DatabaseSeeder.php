<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['dominican-republic', 'Dominican Republic', 'República Dominicana', -72.01, 17.47, -68.32, 19.93],
            ['hispaniola', 'Hispaniola (Dominican Republic · Haiti)', 'La Hispaniola (República Dominicana · Haití)', -74.884, 16.868, -65.417, 22.628],
            ['cuba', 'Cuba', 'Cuba', -84.587, 18.709, -74.213, 24.934],
            ['gran-chaco', 'Gran Chaco (Argentina · Paraguay · Bolivia)', 'Gran Chaco (Argentina · Paraguay · Bolivia)', -70, -35, -55, -15],
            ['cerrado', 'Cerrado & Amazon arc of deforestation (Brazil)', 'Cerrado y arco de deforestación amazónico (Brasil)', -65, -18, -42, -2],
        ];

        foreach ($areas as [$slug, $name, $nameEs, $west, $south, $east, $north]) {
            Area::query()->updateOrCreate(['slug' => $slug], compact('name', 'west', 'south', 'east', 'north') + ['name_es' => $nameEs]);
        }
    }
}
