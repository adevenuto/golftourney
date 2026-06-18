<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire the single-tenant columns now superseded by per-league structures:
     * golfers.handicap → golfer_league.handicap, users.role → league_user.role,
     * rounds.course_name → derived from the round's league/course.
     */
    public function up(): void
    {
        Schema::table('golfers', function (Blueprint $table) {
            $table->dropColumn('handicap');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        if (Schema::hasColumn('rounds', 'course_name')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropColumn('course_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('golfers', function (Blueprint $table) {
            $table->decimal('handicap', 4, 2)->nullable()->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('player');
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->string('course_name')->nullable();
        });
    }
};
