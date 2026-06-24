<?php

namespace App\Http\Controllers;

use App\Services\HandicapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * The league hub: the user's leagues + a place to create/switch.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $leagues = DB::table('leagues as l')
            ->join('league_user as lu', 'lu.league_id', '=', 'l.id')
            ->leftJoin('courses as c', 'c.id', '=', 'l.course_id')
            ->where('lu.user_id', $user->id)
            ->select('l.id', 'l.name', 'lu.role', 'l.course_rating', 'l.slope_rating', 'c.club_name', 'c.course_name')
            ->selectSub(
                DB::table('league_user')->selectRaw('count(*)')->whereColumn('league_user.league_id', 'l.id'),
                'golfers_count'
            )
            ->orderBy('l.name')
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'role' => $l->role,
                'club_name' => $l->club_name,
                'course_name' => $l->course_name,
                'course_rating' => $l->course_rating,
                'slope_rating' => $l->slope_rating,
                'golfers_count' => $l->golfers_count,
                'is_current' => $l->id === $user->current_league_id,
            ]);

        // The signed-in user's own numbers, mirroring the handicap header: the
        // portable Index + total rounds always, plus a Course Handicap for their
        // active league (null when they're not in one).
        $league = $user->currentLeague;

        return Inertia::render('Dashboard', [
            'leagues' => $leagues,
            'stats' => [
                'index' => $this->handicaps->formatIndex($user->effectiveHandicapIndex()),
                'rounds' => $user->rounds()->count(),
                'course_handicap' => $league ? $this->handicaps->courseHandicap($user, $league) : null,
                'has_league' => (bool) $league,
            ],
        ]);
    }
}
