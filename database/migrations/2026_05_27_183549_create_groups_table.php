<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('name');                   // "División A"
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['league_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
