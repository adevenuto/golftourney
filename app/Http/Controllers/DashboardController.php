<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct()
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
            ->where('lu.user_id', $user->id)
            ->select('l.id', 'l.name', 'lu.role', 'l.course_rating', 'l.slope_rating')
            ->selectSub(
                DB::table('golfer_league')->selectRaw('count(*)')->whereColumn('golfer_league.league_id', 'l.id'),
                'golfers_count'
            )
            ->orderBy('l.name')
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'role' => $l->role,
                'course_rating' => $l->course_rating,
                'slope_rating' => $l->slope_rating,
                'golfers_count' => $l->golfers_count,
                'is_current' => $l->id === $user->current_league_id,
            ]);

        return Inertia::render('Dashboard', [
            'leagues' => $leagues,
        ]);
    }
}
