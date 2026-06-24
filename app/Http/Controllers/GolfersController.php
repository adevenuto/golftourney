<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGolfersRequest;
use App\Http\Requests\UpdateGolferRequest;
use App\Models\League;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GolfersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the current league's golfer roster.
     */
    public function index(Request $request): Response
    {
        $league = $request->user()->currentLeague;

        return Inertia::render('Golfers/Index', [
            'golfers' => $league
                ? Cache::rememberForever(
                    $league->rosterCacheKey(),
                    fn () => $this->rosterQuery($league)->orderByDesc('number_of_rounds')->get()
                )
                : [],
        ]);
    }

    /**
     * Download the league's handicaps as a PDF (honouring sort/search params).
     */
    public function exportPdf(Request $request): SymfonyResponse
    {
        $league = $request->user()->currentLeague;
        abort_unless((bool) $league, 404);

        $allowedSorts = ['last_name', 'handicap', 'number_of_rounds'];
        $sort = in_array($request->query('sort'), $allowedSorts, true)
            ? $request->query('sort')
            : 'last_name';
        $direction = $request->query('dir') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $query = $this->rosterQuery($league);

        foreach (array_filter(preg_split('/\s+/', $search)) as $token) {
            $query->where(function (Builder $q) use ($token) {
                $like = '%'.$token.'%';
                $q->where('u.first_name', 'like', $like)
                    ->orWhere('u.last_name', 'like', $like)
                    ->orWhere('u.email', 'like', $like)
                    ->orWhere('u.phone', 'like', $like);
            });
        }

        $query->orderBy($sort, $direction);
        if ($sort === 'last_name') {
            $query->orderBy('u.first_name', $direction);
        }

        return Pdf::loadView('pdf.golfers', [
            'golfers' => $query->get(),
            'league' => $league,
            'generatedAt' => now(),
            'search' => $search,
        ])->download(Str::slug($league->name).'-handicaps.pdf');
    }

    /**
     * Autocomplete search over golfers the user can already see (those sharing
     * one of their leagues), excluding anyone already in the current league.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 3) {
            return response()->json(['golfers' => []]);
        }

        $user = $request->user();
        $leagueIds = $this->visibleLeagueIds($request);
        $currentId = $user->current_league_id;

        if ($leagueIds->isEmpty()) {
            return response()->json(['golfers' => []]);
        }

        $golfers = DB::table('users as u')
            ->join('league_user as lu', 'lu.user_id', '=', 'u.id')
            ->whereIn('lu.league_id', $leagueIds)
            ->whereNotExists(fn (Builder $q) => $q->select(DB::raw(1))
                ->from('league_user as cur')
                ->whereColumn('cur.user_id', 'u.id')
                ->where('cur.league_id', $currentId))
            ->where(function (Builder $q) use ($term) {
                foreach (array_filter(preg_split('/\s+/', $term)) as $token) {
                    $like = '%'.$token.'%';
                    $q->where(fn (Builder $sub) => $sub
                        ->where('u.first_name', 'like', $like)
                        ->orWhere('u.last_name', 'like', $like)
                        ->orWhere('u.email', 'like', $like));
                }
            })
            ->distinct()
            ->orderBy('u.last_name')
            ->limit(12)
            ->get(['u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.phone']);

        return response()->json([
            'golfers' => $golfers->map(fn ($g): array => [
                'id' => $g->id,
                'first_name' => $g->first_name,
                'last_name' => $g->last_name,
                'email' => $g->email,
                'phone' => $g->phone,
                'via' => $this->viaLeagueName((int) $g->id, $leagueIds, $currentId),
            ])->all(),
        ]);
    }

    /**
     * Add a batch of golfers to the current league — each row is either an
     * existing golfer (reused) or a brand-new one.
     */
    public function store(StoreGolfersRequest $request): RedirectResponse
    {
        $league = $request->user()->currentLeague;
        abort_unless((bool) $league, 404);

        $leagueIds = $this->visibleLeagueIds($request);
        $added = 0;

        DB::transaction(function () use ($request, $league, $leagueIds, &$added) {
            foreach ($request->validated()['golfers'] as $row) {
                $golfer = $this->resolveUser($row, $leagueIds);

                if (! $golfer) {
                    continue;
                }

                $golfer->leagues()->syncWithoutDetaching([
                    $league->id => ['role' => 'player', 'handicap' => 0],
                ]);
                $added++;
            }
        });

        $league->forgetRosterCache();

        $noun = $added === 1 ? 'golfer' : 'golfers';

        return back()->with('success', "{$added} {$noun} added.");
    }

    /**
     * Resolve a batch row to a user: reuse an existing (authorized) user, dedup
     * a new one by email within the user's scope, or create a login-less one.
     *
     * @param  array<string, mixed>  $row
     * @param  Collection<int, int>  $leagueIds
     */
    private function resolveUser(array $row, $leagueIds): ?User
    {
        // Reuse: only allowed for users the acting user can already see.
        if (! empty($row['golfer_id'])) {
            return User::query()
                ->whereKey($row['golfer_id'])
                ->whereHas('leagues', fn ($q) => $q->whereIn('leagues.id', $leagueIds))
                ->first();
        }

        // New: dedup by email within the user's visible scope.
        $email = $row['email'] ?? null;

        if ($email) {
            $existing = User::query()
                ->where('email', $email)
                ->whereHas('leagues', fn ($q) => $q->whereIn('leagues.id', $leagueIds))
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return User::create([
            'first_name' => strtolower((string) $row['first_name']),
            'last_name' => strtolower((string) $row['last_name']),
            'email' => $email,
            'phone' => $row['phone'] ?? null,
            'password' => null, // roster user, not a login account
        ]);
    }

    /**
     * League ids the acting user belongs to (the bounds of who they can see).
     *
     * @return Collection<int, int>
     */
    private function visibleLeagueIds(Request $request): Collection
    {
        return DB::table('league_user')
            ->where('user_id', $request->user()->id)
            ->pluck('league_id');
    }

    /**
     * Name of one of the user's leagues (other than the current) the golfer is
     * in, for the "in <league>" search hint.
     *
     * @param  Collection<int, int>  $leagueIds
     */
    private function viaLeagueName(int $golferId, $leagueIds, ?int $currentId): ?string
    {
        $name = DB::table('league_user as lu')
            ->join('leagues as l', 'l.id', '=', 'lu.league_id')
            ->where('lu.user_id', $golferId)
            ->whereIn('lu.league_id', $leagueIds)
            ->when($currentId, fn ($q) => $q->where('lu.league_id', '!=', $currentId))
            ->orderBy('l.name')
            ->value('l.name');

        return is_string($name) ? $name : null;
    }

    /**
     * Update a golfer (must be in the current league).
     */
    public function update(UpdateGolferRequest $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        $user->update([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        // Name/email/phone live on the user, so every league they're in is stale.
        $user->leagues()->get()->each->forgetRosterCache();

        return back()->with('success', 'Golfer updated.');
    }

    /**
     * Remove a golfer from the current league. Login-less roster users left with
     * no leagues are deleted entirely; login accounts are only detached.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $league = $this->authorizeUser($request, $user);

        $user->rounds()->where('league_id', $league->id)->delete();
        $user->leagues()->detach($league->id);

        if ($user->leagues()->count() === 0 && ! $user->canLogin()) {
            $user->delete();
        }

        $league->forgetRosterCache();

        return back()->with('success', 'Golfer removed.');
    }

    /**
     * Roster query for a league: member fields + pivot handicap + per-league round count.
     */
    private function rosterQuery(League $league): Builder
    {
        return DB::table('users as u')
            ->join('league_user as lu', 'lu.user_id', '=', 'u.id')
            ->where('lu.league_id', $league->id)
            ->select('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.phone', 'lu.handicap')
            ->selectSub(
                DB::table('rounds')
                    ->selectRaw('count(*)')
                    ->whereColumn('rounds.user_id', 'u.id')
                    ->where('rounds.league_id', $league->id),
                'number_of_rounds'
            );
    }

    /**
     * Ensure the user belongs to the acting user's current league; return it.
     */
    private function authorizeUser(Request $request, User $user): League
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $user->leagues()->whereKey($league->id)->exists(), 404);

        return $league;
    }
}
