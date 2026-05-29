<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable(); // null = current member
            $table->timestamps();

            $table->index(['group_id', 'left_at']);
            $table->index(['player_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_player');
    }
};
