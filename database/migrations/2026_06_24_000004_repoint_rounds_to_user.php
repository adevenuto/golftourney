<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finalize rounds on user_id now that every round has been re-keyed: make
     * user_id NOT NULL, constrain it to users (cascade on delete), and drop the
     * obsolete golfer_id column. DDL only — runs after the data backfill.
     *
     * The FK is skipped on SQLite (test DB), which would need a full table
     * rebuild to add it; the app cascades rounds in code anyway.
     */
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('rounds', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('rounds', 'golfer_id')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropColumn('golfer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('golfer_id')->nullable()->after('id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
