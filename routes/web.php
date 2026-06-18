<?php

use App\Http\Controllers\GolfersController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoundsController;
use Illuminate\Foundation\Application;
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
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Golfers & rounds (Inertia).
    Route::get('/golfers', [GolfersController::class, 'index'])->name('golfers.index');
    Route::get('/golfers/{golfer}/rounds', [RoundsController::class, 'index'])->name('golfers.rounds');

    // Profile (Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin-only write & delete actions.
    Route::middleware('admin')->group(function () {
        Route::post('/golfers', [GolfersController::class, 'store'])->name('golfers.store');
        Route::put('/golfers/{golfer}', [GolfersController::class, 'update'])->name('golfers.update');
        Route::delete('/golfers/{golfer}', [GolfersController::class, 'destroy'])->name('golfers.destroy');

        Route::post('/golfers/{golfer}/rounds', [RoundsController::class, 'store'])->name('rounds.store');
        Route::put('/rounds/{round}', [RoundsController::class, 'update'])->name('rounds.update');
        Route::delete('/rounds/{round}', [RoundsController::class, 'destroy'])->name('rounds.destroy');
    });
});

require __DIR__.'/auth.php';
