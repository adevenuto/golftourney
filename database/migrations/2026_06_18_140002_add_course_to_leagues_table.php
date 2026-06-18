<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a league to a catalog course + chosen teebox (provenance/display).
     * No DB-level FK so the column adds cleanly on SQLite too; the league's own
     * course_rating/slope_rating remain the source of truth for handicaps.
     */
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('owner_id')->index();
            $table->string('teebox')->nullable()->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropColumn(['course_id', 'teebox']);
        });
    }
};
