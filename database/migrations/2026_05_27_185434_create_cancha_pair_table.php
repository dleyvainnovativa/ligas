<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cancha_pair', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pair_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');   // 1..2
            $table->timestamps();

            $table->unique(['cancha_id', 'slot']);
            $table->unique(['cancha_id', 'pair_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancha_pair');
    }
};
