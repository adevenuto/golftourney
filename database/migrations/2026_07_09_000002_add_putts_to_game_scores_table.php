<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track putts per hole alongside strokes (informational — the round score
     * is still gross strokes). Nullable; entered on the live scorecard.
     */
    public function up(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            $table->unsignedTinyInteger('putts')->nullable()->after('strokes');
        });
    }

    public function down(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            $table->dropColumn('putts');
        });
    }
};
