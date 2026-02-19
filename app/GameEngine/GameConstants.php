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

    const SANTO_SHOP_EXPIRY = 3; // Durata in turni dei santi acquistati

    const MAX_BLOOD_PER_PLAYER = 20; // Limite massimo di sangue che un giocatore può accumulare

    const SANTI = [
        'BIA' => SanBiagio::class,
        'PAN' => SanPantaleone::class,
        'CAT' => SantaCaterina::class
    ];

    const CARD_SUIT_SHOP_ADDED_VALUES = [
        'D' => 3,
        'C' => 2,
        'S' => 1,
        'B' => 0
    ];

    public static function getRandomValue(): int
    {
        return self::VALUES[array_rand(self::VALUES)];
    }

    public static function getRandomSuit(): string
    {
        return self::SUITS[array_rand(self::SUITS)];
    }

    public static function getRandomSanto(): string
    {
        // array_rand returns a random key from the array
        $randomKey = array_rand(self::SANTI);

        // Return the value associated with that key (the ::class string)
        return self::SANTI[$randomKey];
    }
}
