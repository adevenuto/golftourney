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
        Schema::create('golfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('golfer_id')->comment('GolferId from original MSAccess data');
            $table->string('first_name');
            $table->string('last_name');
            $table->decimal('handicap', 4,2)->nullable()->default(00.00);
            $table->string('email')->unique();
            $table->string('phone')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('golfers');
    }
};
