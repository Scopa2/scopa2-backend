<?php

namespace App\GameEngine\Santi;

use App\GameEngine\GameConstants;
use App\GameEngine\GameState;

/*
 * LORE:
 * San Pantaleone é considerato il santo protettore dei giocatori del Lotto
 * Se pregato per 9 notti il santo suggerisce i numeri vincenti per aiutare a sposarsi
 */
class SanPantaleone extends Santo
{
    public static ?string $id = "PAN";

    public static ?string $name = "San Pantaleone";

    public static ?string $description = "Sostituisce le carte della tua mano con carte casuali";

    public static ?int $cost = 3;

    public static function apply(string $pid, GameState $state, array $params = []): void
    {
        foreach ($state->players->get($pid)->hand as $handCard) {
            $state->mutateCardSuit($handCard, GameConstants::getRandomSuit());
            $state->mutateCardValue($handCard, GameConstants::getRandomValue());
        }
    }
}
