<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();

            // Format
            $table->enum('format', ['individual', 'pairs'])->default('individual');

            // Structure
            $table->unsignedInteger('num_jornadas')->default(8);
            $table->decimal('cost', 10, 2)->default(0);

            // Schedule template
            $table->json('days_of_week');   // ["mon","tue","wed",...]
            $table->json('time_slots');     // ["18:00","19:00","21:00"]

            // Penalties
            $table->integer('penalty_suplente')->default(0);
            $table->integer('penalty_no_show')->default(0);

            // Generation rules
            $table->unsignedTinyInteger('jornadas_pares')->default(2);
            $table->unsignedTinyInteger('jornadas_nones')->default(1);

            // Status
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');

            $table->timestamps();

            $table->index(['manager_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
