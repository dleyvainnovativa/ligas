<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('canchas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jornada_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();                // "Cancha 1", auto-generated
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['jornada_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canchas');
    }
};
