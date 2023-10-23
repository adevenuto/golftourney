<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
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
        try {
            $users = User::all();
            return response()->json(['users' => $users], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // public function create()
    // {
    //     return view('users.create');
    // }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'handicap' => 'required|numeric'
            ]);
            
            $random = Str::random(10);

            // \Log::info($random);
            // \Log::info($request);

            $user = new User();
            $user->name = $request->name;
            $user->email = $random."_testuser@test.com";
            $user->handicap = $request->handicap;
            $user->role = 'player';
            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json(['user' => $user], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // public function edit(User $user)
    // {
    //     return view('users.edit', compact('user'));
    // }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->password) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    public function activeTournament()
    {
        return Auth::user()->hasActiveTournament();
    }
}