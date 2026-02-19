<?php

namespace App\GameEngine;

class ScopaNotationParser
{
    public static function parse(string $actionString): array
    {
        $actionString = trim($actionString);

        // 1. SHOP: Inizia con $ -> Es: $GEN(3C+4D)
        if (str_starts_with($actionString, '$')) {
            preg_match('/\$([A-Z0-9]+)\((.*)\)/', $actionString, $matches);
            return [
                'type' => GameConstants::TYPE_SHOP_BUY,
                'santo_id' => $matches[1],
                'payment' => !empty($matches[2]) ? explode('+', $matches[2]) : []
            ];
        }

        // 2. MODIFIER: Inizia con @ -> Es: @LUC[P2]
        if (str_starts_with($actionString, '@')) {
            preg_match('/@([A-Z0-9]+)(?:\[(.*)\])?/', $actionString, $matches);
            return [
                'type' => GameConstants::TYPE_SANTO_USE,
                'santo_id' => $matches[1],
                'params' => isset($matches[2]) ? explode('|', $matches[2]) : []
            ];
        }

        // 3. PLAY CARD: Default -> Es: 7Dx7C#
        $isScopa = str_ends_with($actionString, '#');
        $clean = str_replace('#', '', $actionString);

        if (str_contains($clean, 'x')) {
            [$card, $targets] = explode('x', $clean);
            $targets = str_replace(['(',')'], '', $targets); // Pulisci (..)
            $targetList = explode('+', $targets);
        } else {
            $card = $clean;
            $targetList = [];
        }

        return [
            'type' => GameConstants::TYPE_CARD_PLAY,
            'card' => $card,
            'targets' => $targetList,
            'is_scopa' => $isScopa
        ];
    }
}
