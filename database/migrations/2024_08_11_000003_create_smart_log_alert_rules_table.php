<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_log_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 50); // 'threshold', 'anomaly', 'pattern'
            $table->json('conditions');
            $table->string('severity', 20)->default('medium');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('throttle_minutes')->default(60);
            $table->json('notification_channels'); // ['email', 'slack', etc.]
            $table->json('recipients');
            $table->timestamp('last_triggered')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'trigger_type'], 'slar_active_type_idx');
            $table->index(['severity', 'is_active'], 'slar_severity_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_log_alert_rules');
    }
};