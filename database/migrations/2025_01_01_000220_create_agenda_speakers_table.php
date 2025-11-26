<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_slot_id')->constrained('agenda_slots')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('company')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['agenda_slot_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_speakers');
    }
};
