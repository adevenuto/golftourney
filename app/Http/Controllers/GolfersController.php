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
use Illuminate\Support\Carbon;
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
        $already = 0;

        DB::transaction(function () use ($request, $league, $leagueIds, &$added, &$already) {
            foreach ($request->validated()['golfers'] as $row) {
                $golfer = $this->resolveUser($row, $leagueIds, $league);

                if (! $golfer) {
                    continue;
                }

                $result = $golfer->leagues()->syncWithoutDetaching([
                    $league->id => ['role' => 'player'],
                ]);

                // Distinguish a genuine add from someone already on this roster.
                empty($result['attached']) ? $already++ : $added++;
            }
        });

        $league->forgetRosterCache();

        // A real add is a success; "already on your roster" (nothing new) is a warning.
        $type = $added > 0 ? 'success' : 'warning';

        return back()->with($type, $this->addSummary($added, $already));
    }

    /**
     * A human summary of an add-golfers batch, so re-adding someone who's already
     * on the roster reads as clear feedback instead of a silent "added".
     */
    private function addSummary(int $added, int $already): string
    {
        $parts = [];

        if ($added > 0) {
            $parts[] = $added.' '.Str::plural('golfer', $added).' added';
        }
        if ($already > 0) {
            $parts[] = $already.' already on your roster';
        }

        return $parts === [] ? 'No golfers were added.' : ucfirst(implode(' · ', $parts)).'.';
    }

    /**
     * Resolve a batch row to a user: reuse an existing (authorized) user, dedup
     * a new one by email within the user's scope, or create a login-less one.
     *
     * @param  array<string, mixed>  $row
     * @param  Collection<int, int>  $leagueIds
     */
    private function resolveUser(array $row, $leagueIds, League $league): ?User
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

        $rowEmail = $email ? mb_strtolower(trim((string) $email)) : null;

        if ($rowEmail) {
            $existing = User::query()
                ->whereRaw('lower(email) = ?', [$rowEmail])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $first = strtolower(trim((string) ($row['first_name'] ?? '')));
        $last = strtolower(trim((string) ($row['last_name'] ?? '')));

        // Guard against a same-named duplicate already on THIS roster. Reuse them
        // unless both sides carry a *different* email — proof they're genuinely two
        // people who happen to share a name (a name-only row can't be told apart,
        // so it always reuses). This is what stops the "add Test User twice" dupe.
        if ($first !== '' && $last !== '') {
            $dupe = User::query()
                ->whereRaw('lower(first_name) = ?', [$first])
                ->whereRaw('lower(last_name) = ?', [$last])
                ->whereHas('leagues', fn ($q) => $q->where('leagues.id', $league->id))
                ->first();

            if ($dupe) {
                $dupeEmail = $dupe->email ? mb_strtolower(trim($dupe->email)) : null;
                $distinctPerson = $rowEmail !== null && $dupeEmail !== null && $rowEmail !== $dupeEmail;

                if (! $distinctPerson) {
                    return $dupe;
                }
            }
        }

        return User::create([
            'first_name' => $first,
            'last_name' => $last,
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

        // Once a golfer has their own login they manage their own account — an
        // admin must not be able to change their email/details (that would open
        // an account-takeover path). Their profile is theirs to edit.
        abort_if($user->canLogin(), 403);

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
     *
     * Removing the league's owner (the admin who created it) has no other admin
     * to hand off to, so it tears the whole league down instead — see
     * dissolveLeague().
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $league = $this->authorizeUser($request, $user);

        // Removing the owner deletes the league — only the owner may do it to
        // their own league, so another admin can't tear it down out from under
        // them (nor orphan it by detaching the owner without a teardown).
        if ($user->id === $league->owner_id) {
            abort_unless($request->user()->id === $league->owner_id, 403);

            return $this->dissolveLeague($league);
        }

        $user->rounds()->where('league_id', $league->id)->delete();
        $user->leagues()->detach($league->id);

        if ($user->leagues()->count() === 0 && ! $user->canLogin()) {
            $user->delete();
        }

        $league->forgetRosterCache();

        return back()->with('success', 'Golfer removed.');
    }

    /**
     * Tear down a league when its owner leaves: remove every member (league_user)
     * and delete the league, but keep everyone's rounds by detaching them into
     * standalone casual rounds (rounds are self-contained, so they stay scoreable
     * without a league). No users are deleted — that would cascade their rounds.
     */
    private function dissolveLeague(League $league): RedirectResponse
    {
        $name = $league->name;

        // Keeps everyone's rounds as casual rounds and reselects a next league.
        $league->dissolve();

        return redirect()->route('leagues')
            ->with('success', "“{$name}” was deleted. Members’ rounds were kept.");
    }

    /**
     * Invite a roster player to set up a login: confirm/update their email (it
     * must be unique across accounts), then email them a set-password link (and
     * surface it to the admin to copy, since mail delivery isn't assured). Also
     * used to resend — invited_at is stamped each time.
     */
    public function invite(Request $request, User $user): RedirectResponse
    {
        $league = $this->authorizeUser($request, $user);

        if ($user->canLogin()) {
            throw ValidationException::withMessages(['email' => 'This golfer already has a login.']);
        }

        $email = trim((string) $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ])['email']);

        // Placeholder noreply addresses (used for login-less roster golfers) can't
        // receive an invite — the admin must supply a real, reachable email.
        if (str_ends_with(mb_strtolower($email), '@noreply.com')) {
            throw ValidationException::withMessages(['email' => 'Enter a real email — a noreply address can’t receive an invite.']);
        }

        // An email uniquely identifies a person, so it can't already belong to a
        // different account (case-insensitive, matching how we dedup elsewhere).
        $taken = User::whereRaw('lower(email) = ?', [mb_strtolower($email)])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['email' => 'This email is already in use.']);
        }

        $user->update(['email' => $email]);

        $token = Password::broker('invites')->createToken($user);

        // Email delivery isn't essential — the copyable invite link below is the
        // fallback — so a mailer hiccup must never fail the whole invite.
        $delivered = true;

        try {
            $user->notify(new PlayerInvitation($token, $league->name));
        } catch (\Throwable $e) {
            $delivered = false;
            Log::warning('Player invitation email failed to send; link fallback returned.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $user->update(['invited_at' => now()]);

        // Email + invite state changed, so every roster this golfer is on is stale.
        $user->leagues()->get()->each->forgetRosterCache();

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
     * @return list<array{id: int, first_name: string, last_name: string, email: ?string, phone: ?string, number_of_rounds: int, index: string, index_value: ?float, manual_handicap_index: ?float, has_computed_index: bool, course_handicap: ?int, can_login: bool, invited_at: ?string}>
     */
    private function rosterFor(League $league): array
    {
        $rows = DB::table('users as u')
            ->join('league_user as lu', 'lu.user_id', '=', 'u.id')
            ->where('lu.league_id', $league->id)
            ->select('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.phone', 'u.handicap_index', 'u.manual_handicap_index', 'u.invited_at')
            ->selectRaw('(u.password is not null) as can_login')
            ->selectSub(
                DB::table('rounds')
                    ->selectRaw('count(*)')
                    ->whereColumn('rounds.user_id', 'u.id')
                    ->where('rounds.league_id', $league->id),
                'number_of_rounds'
            )
            ->get();

        // A league-only league computes its index from this league's rounds only
        // — per member, on the fly (the stored index pools everything). Cached,
        // so this only runs on a roster cache miss.
        $scopedIndex = [];

        if ($league->league_only) {
            foreach (User::whereIn('id', $rows->pluck('id'))->get() as $member) {
                $scopedIndex[$member->id] = $this->handicaps->indexForLeague($member, $league);
            }
        }

        return $rows->map(function (object $r) use ($league, $scopedIndex): array {
            // The 18-hole index driving this league: a league-scoped value when
            // league-only, else the global portable one (established index seeds
            // a thin record, computed takes over).
            $effective = $league->league_only
                ? ($scopedIndex[$r->id] ?? null)
                : ($r->handicap_index ?? $r->manual_handicap_index);
            $effective = is_null($effective) ? null : (float) $effective;

            return [
                'id' => (int) $r->id,
                'first_name' => (string) $r->first_name,
                'last_name' => (string) $r->last_name,
                'email' => $r->email === null ? null : (string) $r->email,
                'phone' => $r->phone === null ? null : (string) $r->phone,
                'number_of_rounds' => (int) $r->number_of_rounds,
                'index' => $this->handicaps->formatIndexFor($effective, $league),
                'index_value' => $this->handicaps->displayIndex($effective, $league),
                'manual_handicap_index' => is_null($r->manual_handicap_index) ? null : (float) $r->manual_handicap_index,
                // A computed (global) index exists once the round minimum is met;
                // until then the established index is required and editable.
                'has_computed_index' => ! is_null($r->handicap_index),
                'course_handicap' => $this->handicaps->courseHandicapForIndex($effective, $league),
                'can_login' => (bool) $r->can_login,
                // When they were last sent a login invite (null = never / accepted).
                'invited_at' => $r->invited_at ? Carbon::parse($r->invited_at)->format('M j, Y') : null,
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
