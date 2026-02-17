<?php

namespace App\GameEngine\Santi;

use App\GameEngine\GameState;

class SanBiagio extends Santo
{
    public static ?string $id = "BIA";

    public static ?string $name = "San Biagio";

    public static ?string $description = "Trasforma le carte della tua mano in denari";

    public static ?int $cost = 3;

    public static function apply(string $pid, GameState $state, array $params = []): void
    {
        foreach ($state->players->get($pid)->hand as $handCard) {
           $state->mutateCardSuit($handCard, 'D');
        }
    }
}
