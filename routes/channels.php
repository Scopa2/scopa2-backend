<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Game;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('games', function () {
    return true;
});

Broadcast::channel('{id}_matchmaking_result', function (User $user, $id) {
    return (string) $user->id === (string) $id;
});


// Single player game channel
Broadcast::channel('game_{gameId}_player_{userId}', function (User $user, $gameId, $userId) {
    return Game::where('id', $gameId)
        ->where(function ($query) use ($user) {
            $query->where('player_1_id', $user->id)
                ->orWhere('player_2_id', $user->id);
        })
        ->exists() && (string) $userId === (string) $user->id;
});

// Both players channel
Broadcast::channel('game_{gameId}', function (User $user, $gameId) {
    return Game::where('id', $gameId)
        ->where(function ($query) use ($user) {
            $query->where('player_1_id', $user->id)
                ->orWhere('player_2_id', $user->id);
        });
});
