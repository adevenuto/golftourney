<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInviteController extends Controller
{
    /**
     * Show the "set up your account" page for an invited player.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/AcceptInvite', [
            'email' => $request->query('email'),
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Set the player's password from the invite token, then log them in.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $invited = null;

        $status = Password::broker('invites')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request, &$invited): void {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')->value()),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                // Land them in a league so their handicap view has context.
                if (! $user->current_league_id && ($first = $user->leagues()->first())) {
                    $user->update(['current_league_id' => $first->id]);
                }

                $invited = $user;
            }
        );

        if ($status === Password::PASSWORD_RESET && $invited) {
            Auth::login($invited);

            return redirect()
                ->route('my-handicap')
                ->with('success', 'Welcome — your account is set up.');
        }

        throw ValidationException::withMessages(['email' => [trans($status)]]);
    }
}
