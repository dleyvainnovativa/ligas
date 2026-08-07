<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Convention across all three: NULL = unlimited, an integer = a hard cap.
            // Free baseline: unlimited players, 1 jornada, unlimited groups.
            $table->unsignedInteger('max_players')->nullable()->after('status');
            $table->unsignedInteger('max_jornadas')->nullable()->default(1)->after('max_players');
            $table->unsignedInteger('max_groups')->nullable()->after('max_jornadas');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['max_players', 'max_jornadas', 'max_groups']);
        });
    }
};
