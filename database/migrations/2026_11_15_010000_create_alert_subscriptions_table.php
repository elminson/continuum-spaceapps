<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('locale', 2)->default('en');
            $table->decimal('west', 7, 2);
            $table->decimal('south', 7, 2);
            $table->decimal('east', 7, 2);
            $table->decimal('north', 7, 2);
            $table->string('token', 40)->unique();
            $table->date('last_alert_week')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_subscriptions');
    }
};
