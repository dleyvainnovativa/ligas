<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets one ad target MANY leagues (previously a single league_id). Adds an
 * is_global flag and an ad_league pivot, then migrates existing data:
 *   - ads with league_id = NULL  → is_global = true (shown on all leagues)
 *   - ads with league_id = X      → pivot row (ad, X)
 * The legacy league_id column is kept but no longer drives targeting.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            if (! Schema::hasColumn('ads', 'is_global')) {
                $table->boolean('is_global')->default(false)->after('league_id');
            }
        });

        if (! Schema::hasTable('ad_league')) {
            Schema::create('ad_league', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
                $table->foreignId('league_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['ad_id', 'league_id']);
            });
        }

        // Existing global ads (no league) → is_global.
        DB::table('ads')->whereNull('league_id')->update(['is_global' => true]);

        // Existing league-targeted ads → pivot rows.
        $now = now();
        $rows = DB::table('ads')->whereNotNull('league_id')->get(['id', 'league_id']);
        foreach ($rows as $ad) {
            DB::table('ad_league')->updateOrInsert(
                ['ad_id' => $ad->id, 'league_id' => $ad->league_id],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_league');
        Schema::table('ads', function (Blueprint $table) {
            if (Schema::hasColumn('ads', 'is_global')) {
                $table->dropColumn('is_global');
            }
        });
    }
};
