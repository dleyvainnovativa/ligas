<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('score_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();

            // Exactly one of these is set (enforced in the app layer, not the DB,
            // for shared-hosting portability): player for individual leagues,
            // pair for doubles leagues.
            $table->foreignId('player_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('pair_id')->nullable()->constrained()->cascadeOnDelete();

            // What the bonus is measured in:
            //   'game' → adds to games won (shifts Pts/diff on the leaderboard)
            //   'set'  → adds to rounds/sets won (shifts the Sets tiebreaker)
            $table->enum('unit', ['game', 'set']);

            // Signed: positive = bonus, negative = manual correction/penalty.
            $table->integer('amount');

            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['league_id', 'player_id']);
            $table->index(['league_id', 'pair_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_adjustments');
    }
};
