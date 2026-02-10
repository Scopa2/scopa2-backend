<?php

namespace App\GameEngine\Santi;

use App\GameEngine\GameState;

class SanBiagio extends Santo
{
    public static ?string $id = "BIA";

    public static function apply(string $pid, GameState $state, array $params = []): void
    {
        array_map(function(&$card) {
          $card[1] = 'D';
        },$state->players->get($pid)->hand);
    }
}
