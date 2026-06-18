<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $league = $user?->currentLeague;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    // Role is per-league: the user's role in their current league.
                    'role' => $user->roleIn($league),
                    'current_league' => $league ? [
                        'id' => $league->id,
                        'name' => $league->name,
                    ] : null,
                    // The user's leagues, for the nav switcher.
                    'leagues' => DB::table('league_user as lu')
                        ->join('leagues as l', 'l.id', '=', 'lu.league_id')
                        ->where('lu.user_id', $user->id)
                        ->orderBy('l.name')
                        ->get(['l.id', 'l.name']),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
