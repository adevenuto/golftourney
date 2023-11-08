<?php

namespace App\Http\Controllers;

use App\Models\Golfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function update(Request $request, $id)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:golfers,email,'.$id,
            'handicap' => 'required|numeric|min:0',
        ];

        $messages = [
            'email.unique' => 'The email address is already in use by another golfer.',
        ];

        $this->validate($request, $rules, $messages);

        $golfer = Golfer::find($id);
        if (!$golfer) {
            return response()->json(['message' => 'Golfer not found'], 404);
        }

        $golfer->first_name = $request->input('first_name');
        $golfer->last_name = $request->input('last_name');
        $golfer->email = $request->input('email');
        $golfer->handicap = $request->input('handicap');
        $golfer->phone = $request->input('phone');
        $golfer->save();

        return response()->json(['message' => 'Golfer updated successfully'], 200);
    }

    public function delete($id)
    {   
        try {
            DB::table('golfers')->where('golfer_id', $id)->delete();
            return response()->json(['success' => 'Golfer deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}