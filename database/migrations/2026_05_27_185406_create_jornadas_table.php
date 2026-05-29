<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jornadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');                  // 1, 2, 3...
            $table->enum('status', ['draft', 'scheduled', 'completed'])->default('draft');
            $table->date('window_start')->nullable();           // earliest date for matches in this jornada
            $table->date('window_end')->nullable();             // latest date
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'number']);
            $table->index(['group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas');
    }
};
