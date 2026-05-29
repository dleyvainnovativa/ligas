<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cancha_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');   // 1..4
            $table->timestamps();

            $table->unique(['cancha_id', 'slot']);
            $table->unique(['cancha_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancha_player');
    }
};
