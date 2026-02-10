<?php

namespace App\GameEngine;

/**
 * Rappresenta lo stato di un singolo giocatore durante una partita.
 */
class PlayerState
{
    /**
     * @param array<string> $hand Carte correntemente in mano
     * @param array<string> $captured Mazzo delle carte prese (per punti e shop)
     * @param array<string> $santi Santini acquistati e pronti all'uso
     * @param int $blood Sangue di San Gennaro (il "resto" dello shop)
     * @param int $scope Conteggio scope effettuate
     */
    public function __construct(
        public array $hand = [],
        public array $captured = [],
        public array $santi = [],
        public int $blood = 0,
        public int $scope = 0,
    ) {}

    /**
     * Verifica se il giocatore ha una carta in mano
     */
    public function hasCardInHand(string $cardCode): bool
    {
        return in_array($cardCode, $this->hand);
    }

    /**
     * Verifica se il giocatore ha una carta tra le prese
     */
    public function hasCardCaptured(string $cardCode): bool
    {
        return in_array($cardCode, $this->captured);
    }

    /**
     * Aggiunge una carta alla mano
     */
    public function addToHand(string $card): void
    {
        $this->hand[] = $card;
    }

    /**
     * Rimuove una carta dalla mano
     *
     * @return bool True se la carta è stata rimossa, false se non presente
     */
    public function removeFromHand(string $card): bool
    {
        $idx = array_search($card, $this->hand);
        if ($idx !== false) {
            array_splice($this->hand, $idx, 1);
            return true;
        }
        return false;
    }

    /**
     * Aggiunge una carta alle prese
     */
    public function addToCaptured(string $card): void
    {
        $this->captured[] = $card;
    }

    /**
     * Resetta lo stato del giocatore per un nuovo round
     */
    public function resetForNewRound(): void
    {
        $this->hand = [];
        $this->captured = [];
        $this->scope = 0;
    }

    /**
     * Incrementa il conteggio delle scope
     */
    public function incrementScope(): void
    {
        $this->scope++;
    }

    /**
     * Serializza lo stato per la persistenza o il trasporto
     *
     * @return array{hand: array<string>, captured: array<string>, santi: array<string>, blood: int, scope: int}
     */
    public function toArray(): array
    {
        return [
            'hand' => $this->hand,
            'captured' => $this->captured,
            'santi' => $this->santi,
            'blood' => $this->blood,
            'scope' => $this->scope,
        ];
    }
}
