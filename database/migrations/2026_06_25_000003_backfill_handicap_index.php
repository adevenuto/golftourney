<?php

use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Compute each user's portable WHS Handicap Index from their rounds across
     * all leagues (runs after leagues have holes/par, so differentials are
     * eligible). recalculateFor also busts the roster caches.
     */
    public function up(): void
    {
        $handicaps = app(HandicapService::class);

        User::query()->orderBy('id')->each(function (User $user) use ($handicaps) {
            $handicaps->recalculateFor($user);
        });
    }

    public function down(): void
    {
        // Forward-only.
    }
};
