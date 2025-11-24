<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendee_id')->constrained('users')->cascadeOnDelete();
            $table->string('pair_token')->unique();
            $table->boolean('is_first_timer')->default(false);
            $table->boolean('notes_added')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('connected_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_connections');
    }
};
