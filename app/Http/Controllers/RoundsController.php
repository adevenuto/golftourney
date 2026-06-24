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
                'league_id' => $league->id,
                'recent_window' => $this->handicaps->recentWindowSize($user),
            ],
            'rounds' => $user->rounds()
                ->with(['league:id,name', 'course:id,club_name,course_name'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Round $r): array => [
                    'id' => $r->id,
                    'score' => $r->score,
                    'created_at' => $r->created_at,
                    'origin' => $r->originLabel(),
                    'is_casual' => is_null($r->league_id),
                ]),
            'usedRoundIds' => $this->handicaps->usedRoundIds($user),
        ]);
    }

    /**
     * Store a round for the golfer — a league round (a league they belong to) or,
     * when a course is chosen, a casual round outside league play. Either way the
     * course context is snapshotted onto the round. A golfer may log their own
     * rounds; an admin may log them for any member of their current league.
     */
    public function store(StoreRoundRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $isSelf = $actor->id === $user->id;

        $attributes = [
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ];

        if (($leagueId = $request->integer('league_id')) > 0) {
            // A league round: the golfer must belong to the league, and the actor
            // must be that golfer or an admin of it.
            $league = League::findOrFail($leagueId);
            abort_unless($user->leagues()->whereKey($league->id)->exists(), 404);
            abort_unless($isSelf || $actor->isAdminOf($league), 403);

            $user->rounds()->create($attributes + [
                'league_id' => $league->id,
                'course_id' => $league->course_id,
                'teebox' => $league->teebox,
                'course_rating' => $league->course_rating,
                'slope_rating' => $league->slope_rating,
                'par' => $league->par,
                'holes' => $league->holes,
            ]);
        } else {
            // A casual round at a catalog course.
            abort_unless($isSelf || $this->actorAdminsMemberOf($actor, $user), 403);

            $teebox = is_string($t = $request->input('teebox')) ? $t : null;
            $this->storeCasualRound($user, $request->integer('course_id'), $teebox, $attributes);
        }

        $this->handicaps->recalculateFor($user);

        return back()->with('success', 'Round added.');
    }

    /**
     * Create a casual round, snapshotting the chosen course + teebox context.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function storeCasualRound(User $user, int $courseId, ?string $teebox, array $attributes): void
    {
        $course = Course::findOrFail($courseId);
        $context = $course->teeboxContext($teebox);
        abort_if(is_null($context), 422, 'That course has no usable tee data.');

        $user->rounds()->create($attributes + $context + [
            'league_id' => null,
            'course_id' => $course->id,
            'teebox' => $teebox,
        ]);
    }

    /**
     * Update a round (score/date only — the course context is fixed at creation).
     */
    public function update(UpdateRoundRequest $request, Round $round): RedirectResponse
    {
        $this->authorizeManage($request->user(), $round);

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
        $this->authorizeManage($request->user(), $round);
        $user = $round->user;
        $round->delete();

        $this->handicaps->recalculateFor($user);

        return back()->with('success', 'Round removed.');
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
     * Authorize editing/deleting a round: a golfer may manage any of their OWN
     * rounds (league or casual); an admin may manage any round of a member of
     * their current league.
     */
    private function authorizeManage(User $actor, Round $round): void
    {
        abort_unless(
            $round->user_id === $actor->id || $this->actorAdminsMemberOf($actor, $round->user),
            403
        );
    }

    /**
     * Whether the actor is an admin of their current league and the given user
     * is a member of it — the gate for managing another player's rounds.
     */
    private function actorAdminsMemberOf(User $actor, User $user): bool
    {
        $league = $actor->currentLeague;

        return $league
            && $actor->isAdminOf($league)
            && $user->leagues()->whereKey($league->id)->exists();
    }
}
