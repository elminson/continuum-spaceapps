<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingest_runs', function (Blueprint $table) {
            $table->id();
            $table->string('feed', 20);
            $table->string('status', 10)->default('running');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('read')->nullable();
            $table->unsignedInteger('kept')->nullable();
            $table->unsignedInteger('inserted')->nullable();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->string('error', 200)->nullable();
            $table->index(['started_at']);
            $table->index(['feed', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingest_runs');
    }
};
