<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the legacy non-login users left over from the old dual-table world.
     * Only users 1, 2, 8, 26 ever log in; the rest predate the merge, have no
     * rounds of their own (rounds tie to golfers, not these users), and are
     * superseded by the golfer-derived users created in the next migration.
     *
     * Runs BEFORE the backfill on purpose: the backfill creates ~191 new users
     * with fresh ids, which a static "keep only 1,2,8,26" delete would wipe out
     * if it ran afterwards. Deleting the legacy users first sidesteps that.
     * Their league_user rows cascade; leagues they owned are nulled (owner_id is
     * nullOnDelete, but SQLite doesn't enforce it, so null defensively first).
     *
     * Its own migration so a prod deploy can `migrate --step` and verify counts.
     * No-op on a fresh/empty test DB.
     */
    public function up(): void
    {
        if (! Schema::hasTable('golfers')) {
            return; // post-merge / non-golfer context — nothing to clean up
        }

        $keep = [1, 2, 8, 26];

        DB::transaction(function () use ($keep) {
            DB::table('leagues')
                ->whereNotNull('owner_id')
                ->whereNotIn('owner_id', $keep)
                ->update(['owner_id' => null]);

            DB::table('users')->whereNotIn('id', $keep)->delete();
        });
    }

    public function down(): void
    {
        // Forward-only: deleted users cannot be restored.
    }
};
