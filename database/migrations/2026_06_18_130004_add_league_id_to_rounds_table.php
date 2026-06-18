<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable for the additive A1 step; backfilled, then made non-null in A2.
        // No DB-level FK here so the column can be added on SQLite (test DB) too.
        Schema::table('rounds', function (Blueprint $table) {
            $table->foreignId('league_id')->nullable()->after('golfer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropIndex(['league_id']);
            $table->dropColumn('league_id');
        });
    }
};
