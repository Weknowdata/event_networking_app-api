<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booth_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('booth_code'); // Stable booth identifier (could be a foreign key later).
            $table->date('event_day');
            $table->timestamp('scanned_at');
            $table->string('source', 50)->default('scan'); // attendee_scan, exhibitor_scan, etc.
            $table->string('device_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'booth_code', 'event_day'], 'booth_visit_unique');
            $table->index(['event_day', 'booth_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booth_visits');
    }
};
