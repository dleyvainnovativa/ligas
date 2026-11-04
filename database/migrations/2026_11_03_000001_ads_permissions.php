<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Per-league switch: whether network-wide (global) ads show on this league.
        Schema::table('leagues', function (Blueprint $table) {
            if (! Schema::hasColumn('leagues', 'ads_show_global')) {
                $table->boolean('ads_show_global')->default(true)->after('whatsapp_url');
            }
        });

        // Global ads need league_id = null. The original ads migration made it
        // non-nullable; ensure it's nullable so a fresh deploy supports globals.
        Schema::table('ads', function (Blueprint $table) {
            $table->foreignId('league_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            if (Schema::hasColumn('leagues', 'ads_show_global')) {
                $table->dropColumn('ads_show_global');
            }
        });
        // Leave ads.league_id nullable on rollback — reverting it would break
        // any existing global ads.
    }
};
