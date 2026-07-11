<?php

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// A game's live scorecard — presence channel, joinable only by its players.
// Returning member info powers the "who's online" indicators.
Broadcast::channel('game.{gameId}', function (User $user, int $gameId) {
    $game = Game::find($gameId);

    if (! $game || ! $game->players()->where('user_id', $user->id)->exists()) {
        return false;
    }

    return ['id' => $user->id, 'name' => trim($user->first_name.' '.$user->last_name)];
});
