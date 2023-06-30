<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($uuid)
    {   
        $tournament = Tournament::where('uuid', $uuid)->where(function ($query) {
            $query->where('status', 'created')
                ->orWhere('status', 'active');
        })->first();

        if (!$tournament) abort(404); 
        
        return view('tournament.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($id)
    {   
        $tournament = new Tournament();
        $tournament->tournament_config_id = $id;
        $tournament->user_id = Auth::id();
        $tournament->uuid = Str::uuid();
        $tournament->status = 'created';
        $tournament->save();

        $redirectUrl = '/tournament/'.$tournament->uuid;

        try {
            return response()->json(['redirectUrl' => $redirectUrl], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tournament $tournament)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tournament $tournament)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tournament $tournament)
    {
        //
    }
}
