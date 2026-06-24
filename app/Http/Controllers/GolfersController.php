<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGolfersRequest;
use App\Http\Requests\UpdateGolferRequest;
use App\Models\League;
use App\Models\User;
use App\Notifications\PlayerInvitation;
use App\Services\HandicapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GolfersController extends Controller
{
    public function __construct(private HandicapService $handicaps)
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
                    fn () => collect($this->rosterFor($league))->sortByDesc('number_of_rounds')->values()->all()
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

        $allowedSorts = ['last_name', 'course_handicap', 'index_value', 'number_of_rounds'];
        $sort = in_array($request->query('sort'), $allowedSorts, true)
            ? $request->query('sort')
            : 'last_name';
        $descending = $request->query('dir') === 'desc';
        $search = trim((string) $request->query('search', ''));

        $roster = collect($this->rosterFor($league));

        foreach (array_filter(preg_split('/\s+/', $search)) as $token) {
            $needle = mb_strtolower($token);
            $roster = $roster->filter(fn (array $g): bool => str_contains(
                mb_strtolower($g['first_name'].' '.$g['last_name'].' '.($g['email'] ?? '').' '.($g['phone'] ?? '')),
                $needle
            ));
        }

        if ($sort === 'last_name') {
            $roster = $roster
                ->sortBy(fn (array $g) => mb_strtolower($g['last_name'].' '.$g['first_name']), SORT_REGULAR, $descending)
                ->values();
        } else {
            // Mirror the table: empty Index / Course Handicap always sort last.
            [$withValue, $empty] = $roster->partition(fn (array $g) => ! is_null($g[$sort]));
            $roster = $withValue
                ->sortBy($sort, SORT_REGULAR, $descending)
                ->concat($empty)
                ->values();
        }

        return Pdf::loadView('pdf.golfers', [
            'golfers' => $roster,
            'league' => $league,
            'generatedAt' => now(),
            'search' => $search,
        ])->setPaper('letter', 'landscape')->download(Str::slug($league->name).'-handicaps.pdf');
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

        $results = $golfers->map(fn ($g): array => [
            'id' => $g->id,
            'first_name' => $g->first_name,
            'last_name' => $g->last_name,
            'email' => $g->email,
            'phone' => $g->phone,
            'via' => $this->viaLeagueName((int) $g->id, $leagueIds, $currentId),
            'external' => false,
        ])->all();

        // Cross-account link: a full, exact email reveals an existing account
        // outside your leagues so you can add it. This is a deliberate privacy
        // gate — there is no fuzzy global search, so accounts can't be harvested.
        if (filter_var($term, FILTER_VALIDATE_EMAIL)) {
            $foundIds = array_column($results, 'id');

            $external = DB::table('users as u')
                ->whereRaw('lower(u.email) = ?', [mb_strtolower($term)])
                ->when($foundIds !== [], fn (Builder $q) => $q->whereNotIn('u.id', $foundIds))
                ->whereNotExists(fn (Builder $q) => $q->select(DB::raw(1))
                    ->from('league_user as cur')
                    ->whereColumn('cur.user_id', 'u.id')
                    ->where('cur.league_id', $currentId))
                ->limit(1)
                ->get(['u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.phone']);

            foreach ($external as $g) {
                $results[] = [
                    'id' => $g->id,
                    'first_name' => $g->first_name,
                    'last_name' => $g->last_name,
                    'email' => $g->email,
                    'phone' => $g->phone,
                    'via' => null,
                    'external' => true,
                ];
            }
        }

        return response()->json(['golfers' => $results]);
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
                    $league->id => ['role' => 'player'],
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
        if (! empty($row['golfer_id'])) {
            // Reuse: a user already on one of the acting user's leagues.
            $user = User::query()
                ->whereKey($row['golfer_id'])
                ->whereHas('leagues', fn ($q) => $q->whereIn('leagues.id', $leagueIds))
                ->first();

            if ($user) {
                return $user;
            }

            // Cross-account link: an outside account is allowed only when the row
            // also carries that account's exact email — proof the admin was given
            // it, since search reveals an outside account only on an exact match.
            if (! empty($row['email'])) {
                return User::query()
                    ->whereKey($row['golfer_id'])
                    ->whereRaw('lower(email) = ?', [mb_strtolower(trim((string) $row['email']))])
                    ->first();
            }

            return null;
        }

        // New: an email uniquely identifies a person, so dedup against ALL
        // accounts (attaching the real one) rather than creating a duplicate or
        // colliding on the unique email.
        $email = $row['email'] ?? null;

        if ($email) {
            $existing = User::query()
                ->whereRaw('lower(email) = ?', [mb_strtolower(trim((string) $email))])
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

        $attributes = [
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ];

        // The established index is editable only while the golfer has no
        // computed index; once they qualify the computed value is authoritative,
        // so we leave any prior seed untouched and ignore the (locked) field.
        if ($user->handicap_index === null) {
            $manual = $request->input('manual_handicap_index');
            $attributes['manual_handicap_index'] = ($manual === null || $manual === '') ? null : $manual;
        }

        $user->update($attributes);

        // Name/email/phone/override live on the user, so every league is stale.
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
     * Invite a roster player to set up a login: email them a set-password link
     * (and surface it to the admin to copy, since mail delivery isn't assured).
     */
    public function invite(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        if ($user->canLogin()) {
            throw ValidationException::withMessages(['invite' => 'This golfer already has a login.']);
        }

        if (! $user->email || str_ends_with($user->email, '@noreply.com')) {
            throw ValidationException::withMessages(['invite' => 'Add the player’s real email before inviting them.']);
        }

        $token = Password::broker('invites')->createToken($user);

        // Email delivery isn't essential — the copyable invite link below is the
        // fallback — so a mailer hiccup must never fail the whole invite.
        $delivered = true;

        try {
            $user->notify(new PlayerInvitation($token));
        } catch (\Throwable $e) {
            $delivered = false;
            Log::warning('Player invitation email failed to send; link fallback returned.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()
            ->with('success', $delivered
                ? "Invitation sent to {$user->email}."
                : 'Invite link ready — copy it below (email delivery is unavailable).')
            ->with('invite_link', route('invite.accept', ['token' => $token, 'email' => $user->email]));
    }

    /**
     * The league roster: each member with their per-league round count, portable
     * Handicap Index (formatted + raw for sorting), and derived Course Handicap.
     *
     * @return list<array{id: int, first_name: string, last_name: string, email: ?string, phone: ?string, number_of_rounds: int, index: string, index_value: ?float, manual_handicap_index: ?float, has_computed_index: bool, course_handicap: ?int, can_login: bool}>
     */
    private function rosterFor(League $league): array
    {
        $rows = DB::table('users as u')
            ->join('league_user as lu', 'lu.user_id', '=', 'u.id')
            ->where('lu.league_id', $league->id)
            ->select('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.phone', 'u.handicap_index', 'u.manual_handicap_index')
            ->selectRaw('(u.password is not null) as can_login')
            ->selectSub(
                DB::table('rounds')
                    ->selectRaw('count(*)')
                    ->whereColumn('rounds.user_id', 'u.id')
                    ->where('rounds.league_id', $league->id),
                'number_of_rounds'
            )
            ->get();

        return $rows->map(function (object $r) use ($league): array {
            // Mirror User::effectiveHandicapIndex: the established index only
            // seeds a thin record; the computed index takes over once it exists.
            $effective = $r->handicap_index ?? $r->manual_handicap_index;
            $effective = is_null($effective) ? null : (float) $effective;

            return [
                'id' => (int) $r->id,
                'first_name' => (string) $r->first_name,
                'last_name' => (string) $r->last_name,
                'email' => $r->email === null ? null : (string) $r->email,
                'phone' => $r->phone === null ? null : (string) $r->phone,
                'number_of_rounds' => (int) $r->number_of_rounds,
                'index' => $this->handicaps->formatIndex($effective),
                'index_value' => $effective,
                'manual_handicap_index' => is_null($r->manual_handicap_index) ? null : (float) $r->manual_handicap_index,
                // A computed index exists once the round minimum is met; until
                // then the established index is required and editable.
                'has_computed_index' => ! is_null($r->handicap_index),
                'course_handicap' => $this->handicaps->courseHandicapForIndex($effective, $league),
                'can_login' => (bool) $r->can_login,
            ];
        })->values()->all();
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
