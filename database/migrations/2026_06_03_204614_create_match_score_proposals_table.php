<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_score_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('game_matches')->cascadeOnDelete();
            $table->json('sets');                  // [[6,4],[3,6],[7,5]]
            $table->string('proposer_name');
            $table->string('proposer_token', 64)->nullable(); // cookie-bound id
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->enum('status', ['pending', 'accepted', 'modified', 'rejected', 'superseded'])->default('pending');
            $table->foreignId('superseded_by_id')->nullable()->constrained('match_score_proposals')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_score_proposals');
    }
};
