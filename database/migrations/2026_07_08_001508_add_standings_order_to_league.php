<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Ordered tiebreaker chain, e.g. ["diff","won","rounds"]
            $table->json('standings_order')->nullable()->after('promotion_relegation');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('standings_order');
        });
    }
};
