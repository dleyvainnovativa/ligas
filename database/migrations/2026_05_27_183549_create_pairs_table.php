<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_a_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('player_b_id')->constrained('players')->cascadeOnDelete();
            $table->string('label')->nullable();   // optional "Los Rayos", etc.
            $table->timestamps();

            $table->index('league_id');
            $table->unique(['league_id', 'player_a_id']);
            $table->unique(['league_id', 'player_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairs');
    }
};
