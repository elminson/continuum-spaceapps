<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_es');
            $table->decimal('west', 9, 4);
            $table->decimal('south', 9, 4);
            $table->decimal('east', 9, 4);
            $table->decimal('north', 9, 4);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Raw NASA FIRMS active-fire detections, one row per pixel.
        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->string('instrument', 5);
            $table->string('satellite', 4);
            $table->timestamp('acquired_at');
            $table->date('acq_date');
            $table->double('latitude');
            $table->double('longitude');
            $table->float('frp');
            $table->string('confidence', 8);
            $table->char('daynight', 1);
            $table->string('version', 12);
        });

        // One row per ~1 km grid cell (0.01°), day and instrument: all satellites merged.
        Schema::create('cell_days', function (Blueprint $table) {
            $table->string('instrument', 5);
            $table->date('acq_date');
            $table->integer('cell_lat');
            $table->integer('cell_lon');
            $table->integer('detections');
            $table->float('frp_sum');
            $table->primary(['instrument', 'acq_date', 'cell_lat', 'cell_lon']);
        });

        // Days on which an instrument delivered any data at all.
        Schema::create('coverage_days', function (Blueprint $table) {
            $table->string('instrument', 5);
            $table->date('acq_date');
            $table->primary(['instrument', 'acq_date']);
        });

        Schema::create('area_days', function (Blueprint $table) {
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->date('acq_date');
            $table->string('instrument', 5);
            $table->integer('cell_days');
            $table->integer('detections');
            $table->float('frp_sum');
            $table->primary(['area_id', 'acq_date', 'instrument']);
        });

        Schema::create('area_fits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->double('ratio');
            $table->double('ratio_low');
            $table->double('ratio_high');
            $table->unsignedInteger('days');
            $table->json('validation')->nullable();
            $table->unique(['area_id', 'month']);
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->double('observed');
            $table->double('typical');
            $table->double('spread');
            $table->double('score');
            $table->string('severity', 8);
            $table->unique(['area_id', 'week_start']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['alerts', 'area_fits', 'area_days', 'coverage_days', 'cell_days', 'detections', 'areas'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
