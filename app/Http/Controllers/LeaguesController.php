<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeagueRequest;
use App\Models\League;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaguesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Create a league; the creator becomes its admin and it becomes current.
     */
    public function store(StoreLeagueRequest $request): RedirectResponse
    {
        $user = $request->user();

        $league = League::create([
            'name' => $request->input('name'),
            'owner_id' => $user->id,
            'course_id' => $request->input('course_id'),
            'teebox' => $request->input('teebox'),
            'course_rating' => $request->input('course_rating'),
            'slope_rating' => $request->input('slope_rating'),
            'recent_rounds' => $request->input('recent_rounds'),
            'counting_rounds' => $request->input('counting_rounds'),
        ]);

        $league->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['current_league_id' => $league->id]);

        return redirect()->route('golfers.index')->with('success', "“{$league->name}” created.");
    }

    /**
     * Switch the user's active league (must be a member).
     */
    public function switch(Request $request, League $league): RedirectResponse
    {
        $user = $request->user();
        abort_unless($league->members()->whereKey($user->id)->exists(), 403);

        $user->update(['current_league_id' => $league->id]);

        return back()->with('success', "Switched to “{$league->name}”.");
    }
}
