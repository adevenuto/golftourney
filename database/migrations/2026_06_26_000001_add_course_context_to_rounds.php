<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give each round its own course context so rounds can exist outside league
     * play (casual rounds, league_id null) and still be scored. League rounds
     * snapshot their league's values; casual rounds snapshot the picked course
     * + teebox. HandicapService reads these, falling back to the league.
     */
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('league_id')->index();
            $table->string('teebox')->nullable()->after('course_id');
            $table->decimal('course_rating', 4, 2)->nullable()->after('teebox');
            $table->unsignedSmallInteger('slope_rating')->nullable()->after('course_rating');
            $table->unsignedSmallInteger('par')->nullable()->after('slope_rating');
            $table->unsignedTinyInteger('holes')->nullable()->after('par');
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn(['course_id', 'teebox', 'course_rating', 'slope_rating', 'par', 'holes']);
        });
    }
};
