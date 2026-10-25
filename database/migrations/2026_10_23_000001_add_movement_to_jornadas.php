<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jornadas', function (Blueprint $table) {
            // Per-jornada promotion/relegation override. NULL = inherit the
            // league's global parity rule (movementForJornadaNumber). When set,
            // this many competitors move up AND down from each cancha (symmetric).
            $table->unsignedInteger('movement')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('jornadas', function (Blueprint $table) {
            $table->dropColumn('movement');
        });
    }
};
