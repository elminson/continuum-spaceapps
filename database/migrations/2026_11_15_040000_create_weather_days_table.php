<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NASA POWER daily weather at each area's centre: the drivers of next week's fire.
        Schema::create('weather_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->decimal('t2m', 5, 2)->nullable();
            $table->decimal('rh2m', 5, 2)->nullable();
            $table->decimal('precip', 6, 2)->nullable();
            $table->decimal('ws2m', 5, 2)->nullable();
            $table->unique(['area_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_days');
    }
};
