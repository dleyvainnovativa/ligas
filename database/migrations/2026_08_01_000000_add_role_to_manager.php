<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->enum('role', ['manager', 'admin'])
                ->default('manager')
                ->index()
                ->after('tier_until');
        });

        // Drop the FK first, then alter the column, then re-add the FK.
        // MySQL won't let you change a column that a foreign key references.
        Schema::table('ads', function (Blueprint $table) {
            $table->dropForeign(['league_id']);
        });

        Schema::table('ads', function (Blueprint $table) {
            // NULL league_id === global ad (shown across every league).
            $table->unsignedBigInteger('league_id')->nullable()->change();
            $table->foreign('league_id')
                ->references('id')->on('leagues')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('ads', function (Blueprint $table) {
            $table->dropForeign(['league_id']);
        });

        Schema::table('ads', function (Blueprint $table) {
            // Restoring NOT NULL will fail if any global ads exist — clear them first.
            $table->unsignedBigInteger('league_id')->nullable(false)->change();
            $table->foreign('league_id')
                ->references('id')->on('leagues')
                ->cascadeOnDelete();
        });
    }
};
