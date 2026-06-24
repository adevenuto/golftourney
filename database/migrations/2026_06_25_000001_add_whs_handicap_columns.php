<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive schema for the WHS portable-handicap rework (v6):
     *  - users.handicap_index: the player's computed WHS Handicap Index
     *    (portable, pooled across all their rounds; null = not established).
     *  - users.manual_handicap_index: an admin-entered override (e.g. a known
     *    USGA index) that takes precedence over the computed one.
     *  - leagues.holes / leagues.par: needed to derive a Course Handicap
     *    (Index × slope/113 + (CR − par)) and to scale 9-hole rounds.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('handicap_index', 4, 1)->nullable()->after('phone');
            $table->decimal('manual_handicap_index', 4, 1)->nullable()->after('handicap_index');
        });

        Schema::table('leagues', function (Blueprint $table) {
            $table->unsignedTinyInteger('holes')->default(18)->after('teebox');
            $table->unsignedSmallInteger('par')->nullable()->after('holes');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['holes', 'par']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['handicap_index', 'manual_handicap_index']);
        });
    }
};
