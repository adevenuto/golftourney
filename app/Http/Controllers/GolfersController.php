<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGolferRequest;
use App\Http\Requests\UpdateGolferRequest;
use App\Models\Golfer;
use App\Models\League;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ? $this->rosterQuery($league)->orderByDesc('number_of_rounds')->get()
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
                $q->where('g.first_name', 'like', $like)
                    ->orWhere('g.last_name', 'like', $like)
                    ->orWhere('g.email', 'like', $like)
                    ->orWhere('g.phone', 'like', $like);
            });
        }

        $query->orderBy($sort, $direction);
        if ($sort === 'last_name') {
            $query->orderBy('g.first_name', $direction);
        }

        return Pdf::loadView('pdf.golfers', [
            'golfers' => $query->get(),
            'league' => $league,
            'generatedAt' => now(),
            'search' => $search,
        ])->download(Str::slug($league->name).'-handicaps.pdf');
    }

    /**
     * Store a new golfer and add them to the current league.
     */
    public function store(StoreGolferRequest $request): RedirectResponse
    {
        $league = $request->user()->currentLeague;
        abort_unless((bool) $league, 404);

        $golfer = Golfer::create([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        $golfer->leagues()->attach($league->id, ['handicap' => 0]);

        return back()->with('success', 'Golfer added.');
    }

    /**
     * Update a golfer (must be in the current league).
     */
    public function update(UpdateGolferRequest $request, Golfer $golfer): RedirectResponse
    {
        $this->authorizeGolfer($request, $golfer);

        $golfer->update([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return back()->with('success', 'Golfer updated.');
    }

    /**
     * Remove a golfer from the current league (and delete the golfer entirely
     * if they're left with no leagues).
     */
    public function destroy(Request $request, Golfer $golfer): RedirectResponse
    {
        $league = $this->authorizeGolfer($request, $golfer);

        $golfer->rounds()->where('league_id', $league->id)->delete();
        $golfer->leagues()->detach($league->id);

        if ($golfer->leagues()->count() === 0) {
            $golfer->delete();
        }

        return back()->with('success', 'Golfer removed.');
    }

    /**
     * Roster query for a league: golfer fields + pivot handicap + per-league round count.
     */
    private function rosterQuery(League $league): Builder
    {
        return DB::table('golfers as g')
            ->join('golfer_league as gl', 'gl.golfer_id', '=', 'g.id')
            ->where('gl.league_id', $league->id)
            ->select('g.id', 'g.first_name', 'g.last_name', 'g.email', 'g.phone', 'gl.handicap')
            ->selectSub(
                DB::table('rounds')
                    ->selectRaw('count(*)')
                    ->whereColumn('rounds.golfer_id', 'g.id')
                    ->where('rounds.league_id', $league->id),
                'number_of_rounds'
            );
    }

    /**
     * Ensure the golfer belongs to the acting user's current league; return it.
     */
    private function authorizeGolfer(Request $request, Golfer $golfer): League
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $golfer->leagues()->whereKey($league->id)->exists(), 404);

        return $league;
    }
}
