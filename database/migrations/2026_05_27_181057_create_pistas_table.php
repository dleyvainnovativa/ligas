<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // "Pista 1", "Cristal", etc.
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['sede_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pistas');
    }
};
