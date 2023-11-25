<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GolfersController;
use App\Http\Controllers\HandicapController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoundsController;

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
    return view('welcome');
});

Auth::routes();

Route::get('/dashboard', [DashboardController::class, 'index'])->name('home');


Route::get('/golfers-list', [GolfersController::class, 'index']);
Route::get('/golfers', [GolfersController::class, 'create']);



Route::delete('/golfers/{id}', [GolfersController::class, 'delete']);
Route::post('/golfers/{id}/edit', [GolfersController::class, 'update']);
Route::post('/create/golfer', [GolfersController::class, 'store']);
Route::get('/golfer/{id}', [GolfersController::class, 'golfer']);


Route::post('/golfers/{id}/add/score/{newScore}', [HandicapController::class, 'store']);
Route::get('/golfers/{id}/rounds', [HandicapController::class, 'rounds']);


Route::get('/rounds/{id}', [RoundsController::class, 'create']);
Route::post('/rounds/edit', [RoundsController::class, 'edit']);
Route::delete('/rounds/{id}', [RoundsController::class, 'delete']);