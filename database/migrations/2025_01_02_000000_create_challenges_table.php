<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Stable identifier (e.g., D1_CONNECT_3)
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points');
            $table->json('requirements'); // Action thresholds for completion.
            $table->enum('frequency', ['daily', 'one_time', 'per_event'])->default('daily');
            $table->unsignedSmallInteger('max_completions_per_user_per_day')->default(1);
            $table->unsignedSmallInteger('applies_to_day')->nullable(); // Event day number (1-based) if scoped.
            $table->date('active_start')->nullable();
            $table->date('active_end')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['is_enabled', 'applies_to_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
