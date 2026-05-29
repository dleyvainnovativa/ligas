<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->unsignedTinyInteger('points_win')->default(3)->after('penalty_no_show');
            $table->unsignedTinyInteger('points_draw')->default(1)->after('points_win');
            $table->unsignedTinyInteger('points_loss')->default(0)->after('points_draw');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['points_win', 'points_draw', 'points_loss']);
        });
    }
};
