<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained()->cascadeOnDelete();

            // Rotation index: 1, 2, 3 in individual mode (americano), always 1 in pairs mode.
            $table->unsignedTinyInteger('rotation_index')->default(1);

            // Schedule
            $table->date('date')->nullable();
            $table->string('time_slot', 5)->nullable();     // "18:00"
            $table->foreignId('pista_id')->nullable()->constrained('pistas')->nullOnDelete();

            // Teams: arrays of player IDs (2 per side in individual, 1 pair id per side in pairs).
            // In pairs mode we still store as player IDs for consistency in standings calcs,
            // OR we can store pair IDs. We'll go with player IDs throughout.
            $table->json('team_a_player_ids');
            $table->json('team_b_player_ids');

            // Pairs mode bookkeeping
            $table->foreignId('team_a_pair_id')->nullable()->constrained('pairs')->nullOnDelete();
            $table->foreignId('team_b_pair_id')->nullable()->constrained('pairs')->nullOnDelete();

            // Results
            $table->json('sets')->nullable();                 // [[6,4],[3,6],[7,5]]
            $table->enum('winner', ['a', 'b', 'draw'])->nullable();
            $table->timestamp('played_at')->nullable();

            // Per-match flags (player ids)
            $table->json('no_show_player_ids')->nullable();
            $table->json('suplente_player_ids')->nullable();

            $table->enum('status', ['unscheduled', 'scheduled', 'completed'])->default('unscheduled');
            $table->timestamps();

            $table->unique(['cancha_id', 'rotation_index']);
            $table->index(['date', 'time_slot', 'pista_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
