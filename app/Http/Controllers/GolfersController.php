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
     * Store a newly created resource in storage.
     * 
     * @return Response
     */
    public function index()
    {
        try {
            $golfers = DB::table('golfers')->get();
            return response()->json(['golfers' => $golfers], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function create()
    {
        return view('golfers.index');
    }

    /**
     * Update existing golfer.
     *
     * @return Response
     */
    public function update(Request $request, $id)
    {   
        try {
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
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {   
        try {
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:golfers'
            ];
    
            $messages = [
                'email.unique' => 'The email address is already in use by another golfer.',
            ];
    
            $this->validate($request, $rules, $messages);
    
            $golfer = new Golfer();
            $golfer->first_name = $request->input('first_name');
            $golfer->last_name = $request->input('last_name');
            $golfer->email = $request->input('email');
            $golfer->phone = $request->input('phone');
            $golfer->save();

            return response()->json(['message' => 'Golfer created successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function delete($id)
    {   
        try {
            DB::table('golfers')->where('id', $id)->delete();
            return response()->json(['success' => 'Golfer deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function golfer($id)
    {
        try {
            $golfer = Golfer::find($id);
            return response()->json(['golfer' => $golfer], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}