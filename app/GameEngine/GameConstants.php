<?php

namespace App\GameEngine;

use App\GameEngine\Santi\SanBiagio;
use App\GameEngine\Santi\SanPantaleone;
use App\GameEngine\Santi\SantaCaterina;

class GameConstants
{
    const SUITS = ['D', 'C', 'S', 'B']; // Denari, Coppe, Spade, Bastoni
    const VALUES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]; // 8=Fante, 9=Cavallo, 10=Re

    const TYPE_SHOP_BUY = 'SHOP_BUY';
    const TYPE_SANTO_USE = 'SANTO_USE';
    const TYPE_CARD_PLAY = 'CARD_PLAY';

    // Placeholder per carte coperte (usato nelle proiezioni pubbliche)
    const CARD_BACK = 'X';

    const GAME_WIN_SCORE = 10;

    const SANTI = [
        'BIA' => SanBiagio::class,
        'PAN' => SanPantaleone::class,
        'CAT' => SantaCaterina::class
    ];

    public static function getCardValue(string $card): int
    {
        if (strlen($card) < 2) return 0;
        $valuePart = substr($card, 0, -1);
        return (int)$valuePart;
    }

    public static function getRandomValue(): int
    {
        return self::VALUES[array_rand(self::VALUES)];
    }

    public static function getRandomSuit(): string
    {
        return self::SUITS[array_rand(self::SUITS)];
    }
}
