<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive schema for the golfers→users merge (v5). Pure DDL so it commits
     * before the data backfill that follows:
     *  - users.email / users.password become nullable (golfer-derived users
     *    have no login and often no email),
     *  - users gains phone (carried over from golfers),
     *  - league_user gains a per-league handicap (was on golfer_league),
     *  - rounds gains user_id alongside golfer_id (backfilled next, golfer_id
     *    dropped later).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Keep the existing unique index; just relax NOT NULL.
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('phone')->nullable()->after('email');
        });

        Schema::table('league_user', function (Blueprint $table) {
            $table->decimal('handicap', 4, 2)->nullable()->default(0)->after('role');
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('golfer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('league_user', function (Blueprint $table) {
            $table->dropColumn('handicap');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->string('password')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
