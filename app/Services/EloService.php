<?php

namespace App\Services;

class EloService
{
    public const K = 32;

    /**
     * Compute ELO deltas given current ratings and player A's score.
     * scoreA: 1 win, 0 loss, 0.5 draw.
     *
     * @return array{a: int, b: int}
     */
    public function compute(int $eloA, int $eloB, float $scoreA): array
    {
        $expectedA = 1.0 / (1.0 + pow(10, ($eloB - $eloA) / 400.0));
        $deltaA = (int) round(self::K * ($scoreA - $expectedA));
        return ['a' => $deltaA, 'b' => -$deltaA];
    }
}
