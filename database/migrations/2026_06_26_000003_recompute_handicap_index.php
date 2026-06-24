<?php

use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Recompute every user's portable Index now that each round carries its own
     * course context (added + backfilled by the 2026_06_26 migrations). The
     * earlier index backfill (2026_06_25_000003) ran BEFORE those columns
     * existed, so on an existing database it produced null indexes — this
     * corrects them. No-op on a fresh/empty DB; idempotent if already correct.
     */
    public function up(): void
    {
        $handicaps = app(HandicapService::class);

        User::query()->orderBy('id')->each(function (User $user) use ($handicaps): void {
            $handicaps->recalculateFor($user);
        });
    }

    public function down(): void
    {
        // Forward-only.
    }
};
