<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Name/email show on every roster this user is on, so those are stale.
        $rosterStale = $user->isDirty(['first_name', 'last_name', 'email']);

        $user->save();

        if ($rosterStale) {
            $user->leagues()->get()->each->forgetRosterCache();
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Grab their leagues before the delete cascades the memberships away, so
        // we can drop them from each cached roster afterward.
        $leagues = $user->leagues()->get();

        Auth::logout();

        $user->delete();

        $leagues->each->forgetRosterCache();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
