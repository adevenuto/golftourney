<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeagueRequest;
use App\Models\League;
use App\Models\Round;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Rename a league (admins of that league only).
     */
    public function update(Request $request, League $league): RedirectResponse
    {
        abort_unless($request->user()->isAdminOf($league), 403);

        $validated = $request->validate(['name' => 'required|string|max:255']);

        $league->update(['name' => $validated['name']]);

        return back()->with('success', 'League renamed.');
    }

    /**
     * Delete a league (admins of that league only), cascading its rounds and
     * its roster — members who also belong to another league, or who can log in,
     * are kept (just detached) so nothing else is corrupted.
     */
    public function destroy(Request $request, League $league): RedirectResponse
    {
        abort_unless($request->user()->isAdminOf($league), 403);

        $name = $league->name;

        DB::transaction(function () use ($league) {
            // This league's rounds.
            Round::where('league_id', $league->id)->delete();

            // Detach members; delete any login-less roster user left leagueless.
            $memberIds = DB::table('league_user')
                ->where('league_id', $league->id)
                ->pluck('user_id');
            $league->members()->detach();

            foreach ($memberIds as $memberId) {
                $stillMember = DB::table('league_user')
                    ->where('user_id', $memberId)
                    ->exists();

                if (! $stillMember) {
                    User::whereKey($memberId)->whereNull('password')->delete();
                }
            }

            // Anyone pointing at this as their current league.
            User::where('current_league_id', $league->id)
                ->update(['current_league_id' => null]);

            $league->delete();
        });

        $league->forgetRosterCache();

        return redirect()->route('dashboard')->with('success', "“{$name}” deleted.");
    }

    /**
     * Switch the user's active league (must be a member).
     */
    public function switch(Request $request, League $league): RedirectResponse
    {
        $user = $request->user();
        abort_unless($league->members()->whereKey($user->id)->exists(), 403);

        $user->update(['current_league_id' => $league->id]);

        // "Enter" the league (e.g. clicking its dashboard card) lands on the roster.
        if ($request->boolean('enter')) {
            return redirect()->route('golfers.index');
        }

        return back()->with('success', "Switched to “{$league->name}”.");
    }
}
