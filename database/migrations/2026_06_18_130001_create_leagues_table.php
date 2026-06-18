<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            // Effective handicap parameters for this league (prefilled from a
            // course/teebox later, but adjustable — e.g. the 9-hole Black League).
            $table->decimal('course_rating', 4, 2)->default(0);
            $table->unsignedSmallInteger('slope_rating')->default(113);
            $table->unsignedSmallInteger('recent_rounds')->default(20);
            $table->unsignedSmallInteger('counting_rounds')->default(8);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
