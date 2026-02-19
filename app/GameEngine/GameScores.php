<?php

namespace App\GameEngine;

/**
 * Rappresenta i punteggi totali dei giocatori durante una partita.
 */
class GameScores
{
    public function __construct(
        public int $p1 = 0,
        public int $p2 = 0,
    ) {}

    /**
     * Aggiunge punti al giocatore specificato
     */
    public function addScore(string $playerId, int $points): void
    {
        match ($playerId) {
            'p1' => $this->p1 += $points,
            'p2' => $this->p2 += $points,
            default => throw new \InvalidArgumentException("Player ID non valido: $playerId"),
        };
    }

    /**
     * Ottiene il punteggio del giocatore specificato
     */
    public function getScore(string $playerId): int
    {
        return match ($playerId) {
            'p1' => $this->p1,
            'p2' => $this->p2,
            default => throw new \InvalidArgumentException("Player ID non valido: $playerId"),
        };
    }

    /**
     * Verifica se un giocatore ha raggiunto il punteggio di vittoria
     */
    public function hasWinner(int $winScore): bool
    {
        return $this->p1 >= $winScore || $this->p2 >= $winScore;
    }

    /**
     * Ritorna il vincitore (chi ha più punti)
     *
     * @return string|null 'p1', 'p2' o null in caso di parità
     */
    public function getWinner(): ?string
    {
        if ($this->p1 > $this->p2) {
            return 'p1';
        } elseif ($this->p2 > $this->p1) {
            return 'p2';
        }
        return null;
    }

    /**
     * Serializza i punteggi per la persistenza o il trasporto
     *
     * @return array{p1: int, p2: int}
     */
    public function toArray(): array
    {
        return [
            'p1' => $this->p1,
            'p2' => $this->p2,
        ];
    }
}
