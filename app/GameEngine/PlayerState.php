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
     * @param array<string> $santi Santi acquistati e pronti all'uso
     * @param int $blood Sangue di San Gennaro (il "resto" dello shop)
     * @param int $solidBlood Sangue che non può essere utilizzato finché sciolto
     * @param int $scope Conteggio scope effettuate
     */
    public function __construct(
        public array $hand = [],
        public array $captured = [],
        public array $santi = [],
        public int   $blood = 0,
        public int   $solidBlood = 0,
        public int   $scope = 0,
    )
    {
    }

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
     * Rimuove una carta dalle prese
     *
     * @param string $card
     * @return void
     */
    public function removeFromCaptured(string $card): void
    {
        $idx = array_search($card, $this->captured);
        if ($idx !== false) {
            array_splice($this->captured, $idx, 1);
        }
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

    public function removeSanto($santoId)
    {
        $idx = array_search($santoId, $this->santi);
        if ($idx !== false) {
            array_splice($this->santi, $idx, 1);
        }
    }

    public function addSanto($santoId)
    {
        $this->santi[] = $santoId;
    }

    public function renderSanti(): array
    {
        return array_map(fn($santoId) => GameConstants::SANTI[$santoId]::serialize(), $this->santi);
    }

    public function addBlood(int $blood): void
    {
        $this->blood = min($this->blood + $blood, GameConstants::MAX_BLOOD_PER_PLAYER);
    }

    public function removeBlood(int $bloodSacrifice): void
    {
        $this->blood = max(0, $this->blood - $bloodSacrifice);
    }

}
