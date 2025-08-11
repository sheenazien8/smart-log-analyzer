<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_log_error_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('pattern_hash', 64)->unique();
            $table->text('pattern_signature');
            $table->string('error_type', 100)->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_seen')->index();
            $table->timestamp('last_seen')->index();
            $table->json('sample_context')->nullable();
            $table->text('suggested_solution')->nullable();
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['severity', 'last_seen'], 'slep_severity_time_idx');
            $table->index(['occurrence_count', 'last_seen'], 'slep_count_time_idx');
            $table->index(['is_resolved', 'last_seen'], 'slep_resolved_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_log_error_patterns');
    }
};