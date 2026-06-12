<?php

namespace App\Listeners;

use App\Events\GameFinished;
use App\Models\Game;
use App\Models\User;
use App\Services\EloService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEloOnGameFinished
{
    public function __construct(private EloService $elo) {}

    public function handle(GameFinished $event): void
    {
        $game = Game::find($event->gameId);
        if (!$game) {
            Log::warning("UpdateEloOnGameFinished: game {$event->gameId} not found");
            return;
        }

        // Skip bot games and incomplete games.
        if ($game->has_bot || !$game->player_2_id) {
            return;
        }

        $winnerPid = $event->results['winner'] ?? null;
        $scoreP1 = match ($winnerPid) {
            'p1' => 1.0,
            'p2' => 0.0,
            default => 0.5,
        };

        DB::transaction(function () use ($game, $scoreP1) {
            $p1 = User::lockForUpdate()->find($game->player_1_id);
            $p2 = User::lockForUpdate()->find($game->player_2_id);
            if (!$p1 || !$p2) return;

            $delta = $this->elo->compute($p1->elo, $p2->elo, $scoreP1);

            $p1->elo = max(0, $p1->elo + $delta['a']);
            $p2->elo = max(0, $p2->elo + $delta['b']);
            $p1->save();
            $p2->save();
        });
    }
}
