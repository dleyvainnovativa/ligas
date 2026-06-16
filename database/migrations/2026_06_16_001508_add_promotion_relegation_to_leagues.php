<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // How many players promote up / relegate down between canchas each jornada
            $table->unsignedTinyInteger('promotion_relegation')->default(1)->after('points_loss');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('promotion_relegation');
        });
    }
};
