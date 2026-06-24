<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the per-league stored handicap. Course Handicaps are now derived
     * on the fly from the user's portable index + the league's rating/slope/par,
     * so storing a per-league value would only go stale.
     */
    public function up(): void
    {
        Schema::table('league_user', function (Blueprint $table) {
            $table->dropColumn('handicap');
        });
    }

    public function down(): void
    {
        Schema::table('league_user', function (Blueprint $table) {
            $table->decimal('handicap', 4, 2)->nullable()->default(0)->after('role');
        });
    }
};
