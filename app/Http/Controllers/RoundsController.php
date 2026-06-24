<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoundRequest;
use App\Http\Requests\UpdateRoundRequest;
use App\Models\Course;
use App\Models\League;
use App\Models\Round;
use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoundsController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * Show a golfer's full round history (league + casual) and current numbers.
     */
    public function index(Request $request, User $user): Response
    {
        $league = $this->leagueFor($request, $user);

        return Inertia::render('Rounds/Index', [
            'golfer' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'index' => $this->handicaps->formatIndex($user->effectiveHandicapIndex()),
                'course_handicap' => $this->handicaps->courseHandicap($user, $league),
                'league' => $league->name,
            ],
            'rounds' => $user->rounds()
                ->with(['league:id,name', 'course:id,club_name,course_name'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Round $r): array => [
                    'id' => $r->id,
                    'score' => $r->score,
                    'created_at' => $r->created_at,
                    'origin' => $this->originLabel($r),
                ]),
            'usedRoundIds' => $this->handicaps->usedRoundIds($user),
        ]);
    }

    /**
     * Store a round for the golfer — a league round (current league) or, when a
     * course is chosen, a casual round outside league play. Either way the
     * course context is snapshotted onto the round.
     */
    public function store(StoreRoundRequest $request, User $user): RedirectResponse
    {
        $league = $this->leagueFor($request, $user);

        $attributes = [
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ];

        if ($courseId = $request->integer('course_id')) {
            $course = Course::findOrFail($courseId);
            $context = $course->teeboxContext($request->input('teebox'));
            abort_if(is_null($context), 422, 'That course has no usable tee data.');

            $user->rounds()->create($attributes + $context + [
                'league_id' => null,
                'course_id' => $course->id,
                'teebox' => $request->input('teebox'),
            ]);
        } else {
            $user->rounds()->create($attributes + [
                'league_id' => $league->id,
                'course_id' => $league->course_id,
                'teebox' => $league->teebox,
                'course_rating' => $league->course_rating,
                'slope_rating' => $league->slope_rating,
                'par' => $league->par,
                'holes' => $league->holes,
            ]);
        }

        $this->handicaps->recalculateFor($user);

        return back()->with('success', 'Round added.');
    }

    /**
     * Update a round (score/date only — the course context is fixed at creation).
     */
    public function update(UpdateRoundRequest $request, Round $round): RedirectResponse
    {
        $this->authorizeRound($request, $round);

        $round->update([
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($round->user);

        return back()->with('success', 'Round updated.');
    }

    /**
     * Delete a round.
     */
    public function destroy(Request $request, Round $round): RedirectResponse
    {
        $this->authorizeRound($request, $round);
        $user = $round->user;
        $round->delete();

        $this->handicaps->recalculateFor($user);

        return back()->with('success', 'Round removed.');
    }

    /**
     * Where a round was played, for display: the league name, or "Casual · course".
     */
    private function originLabel(Round $round): string
    {
        if ($round->league) {
            return $round->league->name;
        }

        $course = $round->course;

        return 'Casual'.($course ? ' · '.($course->club_name ?? $course->course_name) : '');
    }

    /**
     * Resolve the acting user's current league, ensuring the golfer belongs to it.
     */
    private function leagueFor(Request $request, User $user): League
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $user->leagues()->whereKey($league->id)->exists(), 404);

        return $league;
    }

    /**
     * Authorize managing a round: its owner must be a member of the acting
     * admin's current league (the route already enforces admin). Works for
     * league and casual rounds alike.
     */
    private function authorizeRound(Request $request, Round $round): void
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $round->user->leagues()->whereKey($league->id)->exists(), 404);
    }
}
