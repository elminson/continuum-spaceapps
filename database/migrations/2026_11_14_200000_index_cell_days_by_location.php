<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Area totals, weekly maps and the Explore page read cell_days by location.
        DB::statement('create index if not exists cell_days_location_index on cell_days (cell_lat, cell_lon, acq_date)');
    }

    public function down(): void
    {
        DB::statement('drop index if exists cell_days_location_index');
    }
};
