<?php

namespace App\GameEngine;

class GameConstants
{
    const SUITS = ['D', 'C', 'S', 'B']; // Denari, Coppe, Spade, Bastoni
    const VALUES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]; // 8=Fante, 9=Cavallo, 10=Re

    const TYPE_SHOP_BUY = 'SHOP_BUY';
    const TYPE_MODIFIER_USE = 'MODIFIER_USE';
    const TYPE_CARD_PLAY = 'CARD_PLAY';
}
