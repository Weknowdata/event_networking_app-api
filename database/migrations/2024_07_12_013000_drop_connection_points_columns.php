<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_connections', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('user_connections', 'base_points')) {
                $columns[] = 'base_points';
            }

            if (Schema::hasColumn('user_connections', 'total_points')) {
                $columns[] = 'total_points';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_connections', function (Blueprint $table) {
            if (! Schema::hasColumn('user_connections', 'base_points')) {
                $table->unsignedInteger('base_points')->default(0);
            }

            if (! Schema::hasColumn('user_connections', 'total_points')) {
                $table->unsignedInteger('total_points')->default(0);
            }
        });
    }
};
