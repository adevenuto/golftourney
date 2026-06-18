<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGolferRequest;
use App\Http\Requests\UpdateGolferRequest;
use App\Models\Golfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
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

    /**
     * Return a single golfer as JSON (used by the legacy rounds page until 4.3).
     */
    public function golfer(Golfer $golfer): JsonResponse
    {
        return response()->json(['golfer' => $golfer]);
    }
}
