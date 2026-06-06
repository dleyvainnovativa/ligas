<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add the new columns to canchas
        Schema::table('canchas', function (Blueprint $table) {
            $table->date('date')->nullable()->after('label');
            $table->string('time_slot', 5)->nullable()->after('date');
            $table->foreignId('pista_id')->nullable()->after('time_slot')
                ->constrained('pistas')->nullOnDelete();
            $table->enum('status', ['unscheduled', 'scheduled', 'completed'])
                ->default('unscheduled')->after('pista_id');

            $table->index(['date', 'time_slot', 'pista_id']);
        });

        // 2. Copy the schedule from each cancha's rotation_index = 1 match
        DB::statement(<<<SQL
            UPDATE canchas c
            INNER JOIN game_matches gm ON gm.cancha_id = c.id AND gm.rotation_index = 1
            SET
                c.date = gm.date,
                c.time_slot = gm.time_slot,
                c.pista_id = gm.pista_id,
                c.status = CASE
                    WHEN gm.status = 'completed' THEN 'completed'
                    WHEN gm.date IS NOT NULL THEN 'scheduled'
                    ELSE 'unscheduled'
                END
        SQL);

        // 3. Drop the schedule columns from game_matches
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropForeign(['pista_id']);
            $table->dropIndex(['date', 'time_slot', 'pista_id']);
            $table->dropColumn(['date', 'time_slot', 'pista_id']);
        });

        // 4. Keep `status` on game_matches but redefine its meaning:
        // 'pending' = no score entered, 'completed' = score entered.
        // The schedule status lives on the cancha now.
        // We do a data fix: anything that was 'unscheduled' or 'scheduled' is now 'pending'.
        DB::statement("UPDATE game_matches SET status = 'pending' WHERE status IN ('unscheduled', 'scheduled')");

        // 5. Modify the enum to reflect the new semantics
        DB::statement("ALTER TABLE game_matches MODIFY COLUMN status ENUM('pending','completed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Best-effort reversal — schedule data on rotation-1 matches is preserved,
        // but rotation-2 and rotation-3 lose any per-round schedule (which they shouldn't have anyway).
        Schema::table('game_matches', function (Blueprint $table) {
            $table->date('date')->nullable();
            $table->string('time_slot', 5)->nullable();
            $table->foreignId('pista_id')->nullable()->constrained('pistas')->nullOnDelete();
            $table->index(['date', 'time_slot', 'pista_id']);
        });

        DB::statement("ALTER TABLE game_matches MODIFY COLUMN status ENUM('unscheduled','scheduled','completed') NOT NULL DEFAULT 'unscheduled'");

        DB::statement(<<<SQL
            UPDATE game_matches gm
            INNER JOIN canchas c ON c.id = gm.cancha_id
            SET
                gm.date = c.date,
                gm.time_slot = c.time_slot,
                gm.pista_id = c.pista_id,
                gm.status = c.status
        SQL);

        Schema::table('canchas', function (Blueprint $table) {
            $table->dropForeign(['pista_id']);
            $table->dropIndex(['date', 'time_slot', 'pista_id']);
            $table->dropColumn(['date', 'time_slot', 'pista_id', 'status']);
        });
    }
};
