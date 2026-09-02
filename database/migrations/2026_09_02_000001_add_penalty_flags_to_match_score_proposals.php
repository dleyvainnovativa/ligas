<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('match_score_proposals', function (Blueprint $table) {
            // Penalty flags proposed by the public (player id arrays), mirroring
            // game_matches. Display-only for the manager — never auto-applied.
            $table->json('no_show_player_ids')->nullable()->after('sets');
            $table->json('suplente_player_ids')->nullable()->after('no_show_player_ids');
        });
    }

    public function down(): void
    {
        Schema::table('match_score_proposals', function (Blueprint $table) {
            $table->dropColumn(['no_show_player_ids', 'suplente_player_ids']);
        });
    }
};
