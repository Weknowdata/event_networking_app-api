<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_connections', function (Blueprint $table) {
            $table->boolean('user_notes_added')->default(false);
            $table->text('user_notes')->nullable();
            $table->boolean('attendee_notes_added')->default(false);
            $table->text('attendee_notes')->nullable();
        });

        DB::table('user_connections')->where('notes_added', true)->update([
            'user_notes_added' => DB::raw('notes_added'),
            'user_notes' => DB::raw('notes'),
        ]);

        Schema::table('user_connections', function (Blueprint $table) {
            $table->dropColumn(['notes_added', 'notes']);
        });
    }

    public function down(): void
    {
        Schema::table('user_connections', function (Blueprint $table) {
            $table->boolean('notes_added')->default(false);
            $table->text('notes')->nullable();
        });

        DB::table('user_connections')->update([
            'notes_added' => DB::raw('CASE WHEN user_notes_added = 1 OR attendee_notes_added = 1 THEN 1 ELSE 0 END'),
            'notes' => DB::raw('COALESCE(user_notes, attendee_notes)'),
        ]);

        Schema::table('user_connections', function (Blueprint $table) {
            $table->dropColumn([
                'user_notes_added',
                'user_notes',
                'attendee_notes_added',
                'attendee_notes',
            ]);
        });
    }
};
