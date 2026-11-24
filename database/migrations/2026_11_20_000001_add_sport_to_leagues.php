<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            if (! Schema::hasColumn('leagues', 'sport')) {
                // tennis | padel | pickleball. Padel is the existing behavior.
                $table->string('sport', 20)->default('padel')->after('format');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            if (Schema::hasColumn('leagues', 'sport')) {
                $table->dropColumn('sport');
            }
        });
    }
};
