<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGolferRequest;
use App\Http\Requests\UpdateGolferRequest;
use App\Models\Golfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Show the golfer roster.
     */
    public function index(): Response
    {
        return Inertia::render('Golfers/Index', [
            'golfers' => Golfer::query()
                ->withCount(['rounds as number_of_rounds'])
                ->orderByDesc('number_of_rounds')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    /**
     * Download the roster handicaps as a PDF, honouring the current
     * sort/search applied in the UI (passed as query params).
     */
    public function exportPdf(Request $request): SymfonyResponse
    {
        $allowedSorts = ['last_name', 'handicap', 'number_of_rounds'];
        $sort = in_array($request->query('sort'), $allowedSorts, true)
            ? $request->query('sort')
            : 'last_name';
        $direction = $request->query('dir') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $query = Golfer::query()->withCount(['rounds as number_of_rounds']);

        // Match every token against any of the searchable fields (mirrors the UI).
        foreach (array_filter(preg_split('/\s+/', $search)) as $token) {
            $query->where(function ($q) use ($token) {
                $like = '%'.$token.'%';
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $query->orderBy($sort, $direction);
        if ($sort === 'last_name') {
            $query->orderBy('first_name', $direction);
        }

        return Pdf::loadView('pdf.golfers', [
            'golfers' => $query->get(),
            'generatedAt' => now(),
            'search' => $search,
        ])->download('black-league-handicaps.pdf');
    }

    /**
     * Store a new golfer.
     */
    public function store(StoreGolferRequest $request): RedirectResponse
    {
        Golfer::create([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return back()->with('success', 'Golfer added.');
    }

    /**
     * Update an existing golfer.
     */
    public function update(UpdateGolferRequest $request, Golfer $golfer): RedirectResponse
    {
        $golfer->update([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return back()->with('success', 'Golfer updated.');
    }

    /**
     * Delete a golfer.
     */
    public function destroy(Golfer $golfer): RedirectResponse
    {
        $golfer->delete();

        return back()->with('success', 'Golfer removed.');
    }
}
