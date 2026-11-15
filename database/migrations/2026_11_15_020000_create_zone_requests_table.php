<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('email');
            $table->decimal('west', 7, 2);
            $table->decimal('south', 7, 2);
            $table->decimal('east', 7, 2);
            $table->decimal('north', 7, 2);
            $table->string('status', 12)->default('requested');
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('alert_subscriptions', function (Blueprint $table) {
            $table->boolean('email_enabled')->default(true);
            $table->boolean('phone_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('alert_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['email_enabled', 'phone_enabled']);
        });
        Schema::dropIfExists('zone_requests');
    }
};
