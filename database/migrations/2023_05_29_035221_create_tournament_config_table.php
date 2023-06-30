<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_config', function (Blueprint $table) {
            $table->id();
            $table->integer('entry_cost')->nullable();
            $table->integer('skin_prox_cost')->nullable();
            $table->integer('hole_count')->nullable();
            $table->string('course_name')->nullable();
            $table->json('course_details')->nullable();
            $table->string('tournament_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_config');
    }
};
