<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeagueRequest;
use App\Models\Course;
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

        // Holes + par drive the Course Handicap; derive par (and prefer the
        // course's hole count) from the linked catalog course when present.
        $holes = (int) $request->input('holes', 18);
        $par = null;

        if ($courseId = $request->input('course_id')) {
            $course = Course::find($courseId);

            if ($course) {
                $holes = $course->holeCount() ?? $holes;
                $par = $course->parForTeebox($request->input('teebox'), (int) $request->input('slope_rating'));
            }
        }

        $league = League::create([
            'name' => $request->input('name'),
            'owner_id' => $user->id,
            'course_id' => $request->input('course_id'),
            'teebox' => $request->input('teebox'),
            'holes' => $holes,
            'par' => $par,
            'course_rating' => $request->input('course_rating'),
            'slope_rating' => $request->input('slope_rating'),
            'league_only' => $request->boolean('league_only', true),
            'display_nine_hole_index' => $request->boolean('display_nine_hole_index'),
        ]);

        $league->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['current_league_id' => $league->id]);

        return redirect()->route('golfers.index')->with('success', "“{$league->name}” created.");
    }

    /**
     * Update a league's name + handicap settings (admins of that league only).
     */
    public function update(Request $request, League $league): RedirectResponse
    {
        abort_unless($request->user()->isAdminOf($league), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'league_only' => 'boolean',
            'display_nine_hole_index' => 'boolean',
        ]);

        // Only touch a setting when it's actually submitted, so a plain rename
        // doesn't reset the handicap toggles.
        $attributes = ['name' => $validated['name']];

        if ($request->has('league_only')) {
            $attributes['league_only'] = $request->boolean('league_only');
        }

        if ($request->has('display_nine_hole_index')) {
            $attributes['display_nine_hole_index'] = $request->boolean('display_nine_hole_index');
        }

        $league->update($attributes);

        // Both settings change the handicaps the roster shows.
        $league->forgetRosterCache();

        return back()->with('success', 'League updated.');
    }

    /**
     * Delete a league (admins of that league only), cascading its rounds and
     * its roster — members who also belong to another league, or who can log in,
     * are kept (just detached) so nothing else is corrupted.
     */
    public function destroy(Request $request, League $league): RedirectResponse
    {
        // Only the creator (owner) may delete a league they made.
        abort_unless($request->user()->id === $league->owner_id, 403);

        $name = $league->name;

        // Keeps everyone's rounds as casual rounds and reselects a next league.
        $league->dissolve();

        return redirect()->route('leagues')->with('success', "“{$name}” deleted. Members’ rounds were kept.");
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
