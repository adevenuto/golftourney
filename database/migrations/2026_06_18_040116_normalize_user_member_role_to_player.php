<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize the legacy "member" role to "player" so it matches the
     * App\Enums\Role enum and the users table default.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'member')->update(['role' => 'player']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'player')->update(['role' => 'member']);
    }
};
