<?php

namespace App\GameEngine;

use JetBrains\PhpStorm\Pure;

class GameUtilities
{

    /**
     * Estrae il valore numerico da una carta (es. "7D" -> 7)
     * @param string $card
     * @return int
     */
    public static function getCardValue(string $card): int
    {
        if (strlen($card) < 2) return 0;
        $valuePart = substr($card, 0, -1);
        return (int)$valuePart;
    }

    /**
     * Estrae il seme da una carta (es. "7D" -> "D")
     * @param string $card
     * @return string
     */
    public static function getCardSuit(string $card): string
    {
        if (strlen($card) < 2) return '';
        return substr($card, -1);
    }

    /**
     * Calcola il valore di sangue di una carta, considerando il suo valore base e il seme.
     * @param string $cardNotation
     * @return int
     */
    #[Pure]
    public static function getCardBloodValue(string $cardNotation): int
    {
        $baseValue = self::getCardValue($cardNotation);
        if($baseValue === 1)  $baseValue = 11;
        $suit = self::getCardSuit($cardNotation);
        $addedValue = GameConstants::CARD_SUIT_SHOP_ADDED_VALUES[$suit];
        return $baseValue + $addedValue;
    }
}
