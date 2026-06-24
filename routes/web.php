<?php

use App\Http\Controllers\CoursesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GolfersController;
use App\Http\Controllers\HandicapsController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\MyHandicapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoundsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/golfers');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Golfers & rounds (Inertia).
    Route::get('/golfers', [GolfersController::class, 'index'])->name('golfers.index');
    Route::get('/golfers/export/pdf', [GolfersController::class, 'exportPdf'])->name('golfers.export');
    Route::get('/golfers/{user}/rounds', [RoundsController::class, 'index'])->name('golfers.rounds');

    // How handicaps work (explainer).
    Route::get('/handicaps', [HandicapsController::class, 'show'])->name('handicaps');

    // The player's own, league-agnostic handicap page.
    Route::get('/my-handicap', [MyHandicapController::class, 'show'])->name('my-handicap');

    // Round entry/edit — authorization is per-action in RoundsController
    // (admins manage any round; players manage their own casual rounds).
    Route::post('/golfers/{user}/rounds', [RoundsController::class, 'store'])->name('rounds.store');
    Route::put('/rounds/{round}', [RoundsController::class, 'update'])->name('rounds.update');
    Route::delete('/rounds/{round}', [RoundsController::class, 'destroy'])->name('rounds.destroy');

    // Profile (Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Leagues & course catalog (any member can create/switch leagues).
    Route::get('/courses/search', [CoursesController::class, 'search'])->name('courses.search');
    Route::post('/leagues', [LeaguesController::class, 'store'])->name('leagues.store');
    Route::patch('/leagues/{league}', [LeaguesController::class, 'update'])->name('leagues.update');
    Route::delete('/leagues/{league}', [LeaguesController::class, 'destroy'])->name('leagues.destroy');
    Route::post('/leagues/{league}/switch', [LeaguesController::class, 'switch'])->name('leagues.switch');

    // Admin-only write & delete actions.
    Route::middleware('admin')->group(function () {
        Route::get('/golfers/search', [GolfersController::class, 'search'])->name('golfers.search');
        Route::post('/golfers', [GolfersController::class, 'store'])->name('golfers.store');
        Route::put('/golfers/{user}', [GolfersController::class, 'update'])->name('golfers.update');
        Route::delete('/golfers/{user}', [GolfersController::class, 'destroy'])->name('golfers.destroy');

        // Invite a roster player to set up a login.
        Route::post('/golfers/{user}/invite', [GolfersController::class, 'invite'])->name('golfers.invite');
    });
});

require __DIR__.'/auth.php';
