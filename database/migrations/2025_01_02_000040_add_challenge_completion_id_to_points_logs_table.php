<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('points_logs', function (Blueprint $table) {
            $table->foreignId('challenge_completion_id')
                ->nullable()
                ->after('user_connection_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('points_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('challenge_completion_id');
        });
    }
};
