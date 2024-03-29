<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GolfersController;
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
    if (Auth::check())  return redirect('/golfers');
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


Route::get('/golfers/{id}/rounds', [RoundsController::class, 'index']);
Route::get('/rounds/{id}', [RoundsController::class, 'create']);
Route::post('/rounds/edit', [RoundsController::class, 'edit']);
Route::post('/rounds/store', [RoundsController::class, 'store']);
Route::delete('/rounds/{id}', [RoundsController::class, 'delete']);