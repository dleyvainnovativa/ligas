<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->enum('tier', ['free', 'plus', 'pro'])->default('free')->after('email');
            $table->timestamp('tier_until')->nullable()->after('tier');
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->dropIndex(['tier']);
            $table->dropColumn(['tier', 'tier_until']);
        });
    }
};
