<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tournament;

class TournamentController extends Controller
{
    public function index()
    {
        return Tournament::first();
    }
}
