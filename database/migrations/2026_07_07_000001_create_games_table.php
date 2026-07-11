<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "live game": a shared, hole-by-hole scorecard played by 2-4 golfers
     * outside league play. Course context is snapshotted at creation (mirroring
     * a casual round via Course::teeboxContext) so the game is self-contained.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->string('teebox')->nullable();
            $table->unsignedTinyInteger('holes');
            $table->unsignedSmallInteger('par');
            $table->decimal('course_rating', 4, 2);
            $table->unsignedSmallInteger('slope_rating');
            $table->string('status')->default('lobby'); // lobby | active | completed | abandoned
            $table->string('join_code', 8)->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
