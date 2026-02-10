<?php

namespace App\GameEngine;

/**
 * Contenitore tipato per i due giocatori della partita.
 */
class Players
{
    public function __construct(
        public PlayerState $p1 = new PlayerState(),
        public PlayerState $p2 = new PlayerState(),
    ) {}

    /**
     * Ottiene lo stato del giocatore specificato
     */
    public function get(string $playerId): PlayerState
    {
        return match ($playerId) {
            'p1' => $this->p1,
            'p2' => $this->p2,
            default => throw new \InvalidArgumentException("Player ID non valido: $playerId"),
        };
    }

    /**
     * Itera su tutti i giocatori
     *
     * @return iterable<string, PlayerState>
     */
    public function all(): iterable
    {
        yield 'p1' => $this->p1;
        yield 'p2' => $this->p2;
    }

    /**
     * Resetta lo stato di tutti i giocatori per un nuovo round
     */
    public function resetForNewRound(): void
    {
        $this->p1->resetForNewRound();
        $this->p2->resetForNewRound();
    }

    /**
     * Serializza lo stato di tutti i giocatori per la compatibilità con ScoreCalculator
     *
     * @return array{p1: array, p2: array}
     */
    public function toArray(): array
    {
        return [
            'p1' => $this->p1->toArray(),
            'p2' => $this->p2->toArray(),
        ];
    }
}
