<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentConfigController;
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




Route::get('/get/tournament', [TournamentConfigController::class, 'index']);
Route::post('/select/tournament/{id}', [TournamentController::class, 'store']);
Route::get('/tournament/{uuid}', [TournamentController::class, 'create']);
Route::post('/create/user', [UserController::class, 'store']);
Route::get('/user/active/tournament', [UserController::class, 'activeTournament']);
