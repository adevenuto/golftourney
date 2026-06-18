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
    // Golfers & rounds — legacy JSON endpoints; Inertia pages land in later sub-steps.
    Route::get('/golfers-list', [GolfersController::class, 'index']);
    Route::get('/golfers', [GolfersController::class, 'create']);
    Route::get('/golfer/{golfer}', [GolfersController::class, 'golfer']);

    Route::get('/golfers/{golfer}/rounds', [RoundsController::class, 'index']);
    Route::get('/rounds/{id}', [RoundsController::class, 'create']);

    // Profile (Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin-only write & delete actions.
    Route::middleware('admin')->group(function () {
        Route::delete('/golfers/{golfer}', [GolfersController::class, 'delete']);
        Route::post('/golfers/{golfer}/edit', [GolfersController::class, 'update']);
        Route::post('/create/golfer', [GolfersController::class, 'store']);

        Route::post('/rounds/edit', [RoundsController::class, 'edit']);
        Route::post('/rounds/store', [RoundsController::class, 'store']);
        Route::delete('/rounds/{round}', [RoundsController::class, 'delete']);
    });
});

require __DIR__.'/auth.php';
