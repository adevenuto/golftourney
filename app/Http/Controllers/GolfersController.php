<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGolferRequest;
use App\Http\Requests\UpdateGolferRequest;
use App\Models\Golfer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

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
        $golfers = Golfer::query()
            ->withCount(['rounds as number_of_rounds'])
            ->orderByDesc('number_of_rounds')
            ->get();

        return response()->json(['golfers' => $golfers]);
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
     */
    public function update(UpdateGolferRequest $request, Golfer $golfer): JsonResponse
    {
        $golfer->update([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'handicap' => $request->input('handicap'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json(['message' => 'Golfer updated successfully']);
    }

    /**
     * Store golfer and return JSON response.
     */
    public function store(StoreGolferRequest $request): JsonResponse
    {
        Golfer::create([
            'first_name' => strtolower($request->input('first_name')),
            'last_name' => strtolower($request->input('last_name')),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json(['message' => 'Golfer created successfully'], 201);
    }

    /**
     * Delete golfer from storage.
     */
    public function delete(Golfer $golfer): JsonResponse
    {
        $golfer->delete();

        return response()->json(['success' => 'Golfer deleted']);
    }

    /**
     * Get golfer by id.
     */
    public function golfer(Golfer $golfer): JsonResponse
    {
        return response()->json(['golfer' => $golfer]);
    }
}
