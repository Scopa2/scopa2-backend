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


Broadcast::channel('game_{gameId}', function (User $user, $gameId) {
    return Game::where('id', $gameId)
        ->where(function ($query) use ($user) {
            $query->where('player_1_id', $user->id)
                ->orWhere('player_2_id', $user->id);
        })
        ->exists();
});
