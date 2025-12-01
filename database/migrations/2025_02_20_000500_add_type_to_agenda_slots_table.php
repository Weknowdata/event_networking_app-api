<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_slots', function (Blueprint $table) {
            $table->string('type', 50)->default('session')->after('location');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_slots', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
