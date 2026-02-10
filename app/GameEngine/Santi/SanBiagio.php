<?php

namespace App\GameEngine\Santi;

use App\GameEngine\GameState;

class SanBiagio extends Santo
{
    public static ?string $id = "BIA";

    public static ?string $name = "San Biagio";

    public static ?string $description = "Trasforma le carte della tua mano in denari";

    public static function apply(string $pid, GameState $state, array $params = []): void
    {
        $state->players->get($pid)->hand =array_map(function($card) {
            $card[1] = 'D';
            return $card;
        },$state->players->get($pid)->hand);
    }
}
