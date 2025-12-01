<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('event_day');
            $table->unsignedInteger('awarded_points');
            $table->timestamp('completed_at')->useCurrent();
            $table->string('source', 50)->default('auto');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id', 'event_day'], 'challenge_user_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_completions');
    }
};
