<?php

namespace App\GameEngine;

use Exception;

class ScopaEngine
{
    private GameState $state;
    private $rng; // Random Number Generator

    public function __construct(string $seed)
    {
        $this->state = new GameState();
        // Inizializza RNG deterministico
        mt_srand(crc32($seed));

        // Setup Iniziale (Mazzo, Tavolo, Shop)
        $this->initializeGame();
    }

    private function initializeGame() {
        // 1. Crea Mazzo (Logica semplificata per brevità)
        foreach(GameConstants::SUITS as $s) {
            foreach(GameConstants::VALUES as $v) $this->state->deck[] = $v.$s;
        }
        shuffle($this->state->deck); // Mescola usando il seed globale

        // 2. Metti 4 carte a terra
        for($i=0; $i<4; $i++) $this->state->table[] = array_pop($this->state->deck);

        // 3. Dai 3 carte a testa (Esempio)
        $this->distributeCards();

        // 4. Popola Shop
        $this->state->shop = ['GEN', 'LUC', 'ANT']; // Esempio statico
        $this->state->currentTurnPlayer = 'p1'; // Inizia P1
    }

    private function distributeCards() : void
    {
        for($i=0; $i<3; $i++) {
            $this->state->players['p1']['hand'][] = array_pop($this->state->deck);
            $this->state->players['p2']['hand'][] = array_pop($this->state->deck);
        }
    }

    // --- REPLAY SYSTEM ---
    public function replay(array $historyEvents): GameState
    {
        foreach ($historyEvents as $event) {
            // $event è il modello Eloquent GameEvent
            $this->applyAction($event->actor_id, $event->pgn_action);
        }
        return $this->state;
    }

    // --- EXECUTION ---
    public function applyAction(string $actorId, string $pgnAction)
    {
        // 1. Check turno (tranne per setup speciali, qui è strict)
        if ($this->state->currentTurnPlayer !== $actorId) {
            throw new Exception("Non è il turno del giocatore $actorId");
        }

        // 2. Parse
        $action = ScopaNotationParser::parse($pgnAction);

        // 3. Dispatch
        switch ($action['type']) {
            case GameConstants::TYPE_SHOP_BUY:
                $this->handleBuy($actorId, $action['santo_id'], $action['payment']);
                break; // NON cambia turno

            case GameConstants::TYPE_MODIFIER_USE:
                $this->handleModifier($actorId, $action['santo_id'], $action['params']);
                break; // NON cambia turno

            case GameConstants::TYPE_CARD_PLAY:
                $this->handleCardPlay($actorId, $action['card'], $action['targets']);
                // QUI cambia turno
                $this->advanceTurn();
                break;
        }
    }

    private function handleBuy($pid, $santoId, $paymentCards) {
        // Rimuovi carte dal mazzo "captured" del player
        // Aggiungi santo alla mano santi
        // Rimuovi santo dallo shop -> Esempio veloce:
        $key = array_search($santoId, $this->state->shop);
        if ($key !== false) {
            unset($this->state->shop[$key]);
            $this->state->shop = array_values($this->state->shop); // Reindex
        }
    }

    private function handleCardPlay($pid, $card, $targets) {
        // Togli carta dalla mano
        $hand = &$this->state->players[$pid]['hand'];
        $idx = array_search($card, $hand);
        if ($idx !== false) array_splice($hand, $idx, 1);

        if (empty($targets)) {
            // Scarto
            $this->state->table[] = $card;
        } else {
            // Presa
            // Rimuovi target dal tavolo
            $this->state->players[$pid]['captured'][] = $card;
            foreach($targets as $t) {
                $tIdx = array_search($t, $this->state->table);
                if($tIdx !== false) array_splice($this->state->table, $tIdx, 1);
                $this->state->players[$pid]['captured'][] = $t;
            }

            // Controlla se è scopa
            if (empty($this->state->table)) {
                $this->state->players[$pid]['scope'] += 1;
            }
        }
    }

    private function advanceTurn() {
        // Toggle Player
        $this->state->currentTurnPlayer =
            ($this->state->currentTurnPlayer === 'p1') ? 'p2' : 'p1';

        // Redistriuzione carte se entrambe le mani sono vuote
        if (empty($this->state->players['p1']['hand']) && empty($this->state->players['p2']['hand'])) {
            // Se il mazzo è vuoto, termina il round
            if (empty($this->state->deck)) {
                $this->advanceRound();
                return;
            }
            $this->distributeCards();
        }
    }

    private function advanceRound()
    {
        //TODO: Implementa logica di fine round
        dd("ROUND FINITO");
    }

    /**
     * Ritorna lo stato attuale del gioco.
     * Fondamentale per il controller dopo aver applicato le mosse.
     */
    public function getState(): GameState
    {
        return $this->state;
    }

    /**
     * Metodo opzionale utile per il debug:
     * Ritorna un log testuale di cosa l'engine "vede" al momento.
     */
    public function dumpState(): string
    {
        return sprintf(
            "Turno: %s | Tavolo: %s | Mazzo: %d",
            $this->state->currentTurnPlayer,
            implode(', ', $this->state->table),
            count($this->state->deck)
        );
    }
}
