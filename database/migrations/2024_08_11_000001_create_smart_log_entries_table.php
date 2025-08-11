<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_log_entries', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('channel', 50)->nullable()->index();
            $table->string('file_path')->nullable();
            $table->integer('line_number')->nullable();
            $table->string('exception_class')->nullable()->index();
            $table->text('stack_trace')->nullable();
            $table->string('hash', 64)->index();
            $table->timestamp('logged_at')->index();
            $table->timestamps();

            $table->index(['level', 'logged_at'], 'sle_level_time_idx');
            $table->index(['channel', 'logged_at'], 'sle_channel_time_idx');
            $table->index(['hash', 'logged_at'], 'sle_hash_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_log_entries');
    }
};