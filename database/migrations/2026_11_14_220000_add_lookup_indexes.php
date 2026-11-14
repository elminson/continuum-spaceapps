<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create index if not exists alerts_score_index on alerts (score desc)');
        DB::statement('create index if not exists alerts_week_start_index on alerts (week_start desc)');
        DB::statement('create index if not exists saved_boxes_user_id_index on saved_boxes (user_id)');
        DB::statement('create index if not exists area_fits_area_id_index on area_fits (area_id)');
    }

    public function down(): void
    {
        foreach (['alerts_score_index', 'alerts_week_start_index', 'saved_boxes_user_id_index', 'area_fits_area_id_index'] as $index) {
            DB::statement("drop index if exists {$index}");
        }
    }
};
