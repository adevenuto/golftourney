<?php

namespace App\Http\Controllers;

use App\Models\Golfer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;

class GolfersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get golfers with their round counts and return as JSON.
     */
    public function index(): JsonResponse
    {
        try {
            // Correlated subquery for the round count avoids a GROUP BY,
            // keeping the query valid under ONLY_FULL_GROUP_BY (strict SQL mode).
            $golfers = DB::table('golfers as g')
                ->select('g.*')
                ->selectSub(
                    DB::table('rounds')
                        ->selectRaw('count(*)')
                        ->whereColumn('rounds.golfer_id', 'g.id'),
                    'number_of_rounds'
                )
                ->orderByDesc('number_of_rounds')
                ->get();

            return response()->json(['golfers' => $golfers]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Unable to load golfers.'], 500);
        }
    }

    /**
     * Display the view.
     */
    public function create(): View
    {
        return view('golfers.index');
    }

    /**
     * Update golfer and return JSON response.
     *
     * @param int $id
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'handicap' => 'required|numeric|min:0',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $golfer = Golfer::findOrFail($id);
        $golfer->first_name = strtolower($request->input('first_name'));
        $golfer->last_name = strtolower($request->input('last_name'));
        $golfer->email = $request->input('email');
        $golfer->handicap = $request->input('handicap');
        $golfer->phone = $request->input('phone');
        $golfer->save();

        return response()->json(['message' => 'Golfer updated successfully']);
    }

    /**
     * Store golfer and return JSON response.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $golfer = new Golfer();
        $golfer->first_name = strtolower($request->input('first_name'));
        $golfer->last_name = strtolower($request->input('last_name'));
        $golfer->email = $request->input('email');
        $golfer->phone = $request->input('phone');
        $golfer->save();

        return response()->json(['message' => 'Golfer created successfully'], 201);
    }

    /**
     * Delete golfer from storage.
     *
     * @param int $id
     */
    public function delete($id): JsonResponse
    {
        $golfer = Golfer::findOrFail($id);
        $golfer->delete();

        return response()->json(['success' => 'Golfer deleted']);
    }

    /**
     * Get golfer by id.
     *
     * @param int $id
     */
    public function golfer($id): JsonResponse
    {
        $golfer = Golfer::findOrFail($id);

        return response()->json(['golfer' => $golfer]);
    }
}
