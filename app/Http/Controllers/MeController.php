<?php

namespace App\Http\Controllers;

use App\Enums\GameStateEnum;
use App\Models\Game;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function activeGames(Request $request)
    {
        $userId = auth()->id();

        $games = Game::query()
            ->whereIn('status', [GameStateEnum::PLAYING, GameStateEnum::WAITING_FOR_PLAYERS])
            ->where(function ($q) use ($userId) {
                $q->where('player_1_id', $userId)
                  ->orWhere('player_2_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->get(['id', 'player_1_id', 'player_2_id', 'status', 'created_at', 'updated_at']);

        return response()->json([
            'data' => $games->map(function (Game $g) use ($userId) {
                $isP1 = (string)$g->player_1_id === (string)$userId;
                return [
                    'game_id' => $g->id,
                    'status' => $g->status,
                    'my_index' => $isP1 ? 'p1' : 'p2',
                    'opponent_id' => $isP1 ? $g->player_2_id : $g->player_1_id,
                    'created_at' => $g->created_at,
                    'updated_at' => $g->updated_at,
                ];
            }),
        ]);
    }
}
