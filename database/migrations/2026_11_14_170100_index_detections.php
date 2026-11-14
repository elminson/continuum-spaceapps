<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Same pixel from the same satellite at the same minute is the same detection,
        // so overlapping feed pulls never count twice.
        DB::statement('create unique index detections_pixel_unique on detections (satellite, acquired_at, latitude, longitude)');
        DB::statement('create index detections_acq_date_index on detections (acq_date)');
    }

    public function down(): void
    {
        DB::statement('drop index if exists detections_pixel_unique');
        DB::statement('drop index if exists detections_acq_date_index');
    }
};
