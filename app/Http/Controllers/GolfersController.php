<?php

namespace App\Http\Controllers;

use App\Models\Golfer;
use Illuminate\Http\Request;

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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        try {
            $golfers = Golfer::all();
            return response()->json(['golfers' => $golfers], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function create()
    {
        return view('golfers.index');
    }
}