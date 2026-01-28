<?php

namespace App\GameEngine;

class GameUtilities
{
    public static function getCardValue(string $cardNotation): int
    {
        return intval(substr($cardNotation, 0, -1));
    }
}
