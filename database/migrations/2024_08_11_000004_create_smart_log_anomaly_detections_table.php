<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_log_anomaly_detections', function (Blueprint $table) {
            $table->id();
            $table->string('anomaly_type', 50); // 'spike', 'drop', 'pattern_change'
            $table->string('metric', 100); // 'error_rate', 'specific_error', etc.
            $table->decimal('baseline_value', 10, 4);
            $table->decimal('detected_value', 10, 4);
            $table->decimal('deviation_score', 8, 4);
            $table->timestamp('detection_time')->index();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('active'); // 'active', 'resolved', 'ignored'
            $table->timestamps();

            $table->index(['anomaly_type', 'detection_time'], 'sla_anomaly_type_time_idx');
            $table->index(['status', 'detection_time'], 'sla_status_time_idx');
            $table->index(['deviation_score', 'detection_time'], 'sla_deviation_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_log_anomaly_detections');
    }
};