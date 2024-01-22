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
     * Get golfer and return it as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $golfers = DB::table('golfers')->get();
            return response()->json(['golfers' => $golfers], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Display the view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('golfers.index');
    }

    /**
     * Update golfer and return JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @param int $id
     * 
     */
    public function update(Request $request, $id): JsonResponse
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
     * Store golfer and return JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
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
     * Delete golfer from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @param int $id
     */
    public function delete($id): JsonResponse
    {   
        try {
            DB::table('golfers')->where('id', $id)->delete();
            return response()->json(['success' => 'Golfer deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get golfer by id.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @param int $id
     */
    public function golfer($id): JsonResponse
    {
        try {
            $golfer = Golfer::find($id);
            return response()->json(['golfer' => $golfer], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}