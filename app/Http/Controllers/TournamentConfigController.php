<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TournamentConfig;
use Illuminate\Support\Facades\Auth;

class TournamentConfigController extends Controller
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
    
    public function index()
    {       
        return TournamentConfig::first();
    }
}
