<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_slot_id')->constrained()->cascadeOnDelete();
            $table->date('event_day');
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->string('source', 50)->default('scan'); // staff_scanner, self_kiosk, etc.
            $table->string('device_id', 100)->nullable();
            $table->boolean('valid_for_points')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'agenda_slot_id', 'event_day'], 'session_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
