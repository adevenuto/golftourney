<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Read-only catalog of courses (imported from a CSV dump). Rating/slope and
     * per-hole data live inside `layout_data` (per teebox); a league copies the
     * chosen teebox's numbers onto itself when created.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->unique(); // source row id
            $table->unsignedBigInteger('api_course_id')->nullable();

            $table->string('course_name')->index();
            $table->string('club_name')->nullable();
            $table->string('street')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('state', 8)->nullable()->index();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Teeboxes (rating/slope/yardage), hole pars/lengths, geo — verbatim JSON.
            $table->longText('layout_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
