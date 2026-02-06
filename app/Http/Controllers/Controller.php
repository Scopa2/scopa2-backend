<?php

namespace App\Http\Controllers;

use App\Models\Game;

abstract class Controller
{
    public function getLoggedPlayerSecret(): string
    {
        $playerSecret = request()->header('player_secret');
        if (!$playerSecret) {
            throw new \Exception("Player secret not provided");
        }
        return $playerSecret;
    }

    /**
     * @throws \Exception
     */
    public function getLoggedPlayerIndex(Game $game): ?string
    {
        $playerSecret = $this->getLoggedPlayerSecret();
        $playerIndex = $playerSecret === $game->player_1_id ? 'p1' : ($playerSecret === $game->player_2_id ? 'p2' : null);
        if($playerIndex === null) {
            throw new \Exception("Player not part of the game");
        }
        return $playerIndex;
    }
}
