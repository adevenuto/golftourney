<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GolfersController;
use App\Http\Controllers\RoundsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/golfers');
    }

    return view('welcome');
});

Auth::routes();

Route::get('/dashboard', [DashboardController::class, 'index'])->name('home');

Route::get('/golfers-list', [GolfersController::class, 'index']);
Route::get('/golfers', [GolfersController::class, 'create']);
Route::get('/golfer/{golfer}', [GolfersController::class, 'golfer']);

Route::get('/golfers/{golfer}/rounds', [RoundsController::class, 'index']);
Route::get('/rounds/{id}', [RoundsController::class, 'create']); // view only; id read client-side

// Admin-only write & delete actions.
Route::middleware('admin')->group(function () {
    Route::delete('/golfers/{golfer}', [GolfersController::class, 'delete']);
    Route::post('/golfers/{golfer}/edit', [GolfersController::class, 'update']);
    Route::post('/create/golfer', [GolfersController::class, 'store']);

    Route::post('/rounds/edit', [RoundsController::class, 'edit']);
    Route::post('/rounds/store', [RoundsController::class, 'store']);
    Route::delete('/rounds/{round}', [RoundsController::class, 'delete']);
});
