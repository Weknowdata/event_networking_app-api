<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type', 100);
            $table->unsignedInteger('points');
            $table->json('metadata')->nullable();
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_logs');
    }
};
