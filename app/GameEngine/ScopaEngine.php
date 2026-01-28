<?php

namespace App\GameEngine;

use Exception;
use App\GameEngine\ScoreCalculator;

class ScopaEngine
{
    private GameState $state;
    private $rng; // Random Number Generator
    private string $gameSeed; // Seed della partita

    public function __construct(string $seed)
    {
        $this->state = new GameState();
        $this->gameSeed = $seed;

        // Inizializza RNG deterministico per il primo round
        $this->initializeRNG($seed);

        // Setup Iniziale (Mazzo, Tavolo, Shop)
        $this->initializeGame();
    }

    /**
     * Inizializza il RNG con un seed specifico
     */
    private function initializeRNG(string $seed): void
    {
        mt_srand(crc32($seed));
    }

    private function initializeGame() {
        // 1. Crea e mescola il mazzo
        $this->createAndShuffleDeck();

        // 2. Metti 4 carte a terra
        $this->dealTableCards();

        // 3. Dai 3 carte a testa
        $this->distributeCards();

        // 4. Popola Shop (solo al primo round)
        $this->state->shop = ['GEN', 'LUC', 'ANT']; // Esempio statico
        $this->state->currentTurnPlayer = 'p1'; // Inizia P1
    }

    /**
     * Crea un nuovo mazzo di 40 carte e lo mescola
     */
    private function createAndShuffleDeck(): void
    {
        $this->state->deck = [];
        foreach(GameConstants::SUITS as $s) {
            foreach(GameConstants::VALUES as $v) {
                $this->state->deck[] = $v.$s;
            }
        }
        shuffle($this->state->deck); // Mescola usando il seed globale
    }

    /**
     * Mette 4 carte sul tavolo dal mazzo
     */
    private function dealTableCards(): void
    {
        $this->state->table = [];
        for($i=0; $i<4; $i++) {
            $this->state->table[] = array_pop($this->state->deck);
        }
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
            // Presa - Traccia l'ultimo giocatore che ha catturato
            $this->state->lastCapturePlayer = $pid;

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
        // 0. Assegna le carte rimaste sul tavolo all'ultimo giocatore che ha fatto una presa
        if (!empty($this->state->table) && $this->state->lastCapturePlayer !== null) {
            foreach ($this->state->table as $card) {
                $this->state->players[$this->state->lastCapturePlayer]['captured'][] = $card;
            }
            $this->state->table = []; // Svuota il tavolo
        }

        // 1. Calcola i punti del round appena concluso
        $roundScores = $this->calculateRoundScore();

        // 2. Aggiorna i punteggi totali
        $this->state->scores['p1'] += $roundScores['p1'];
        $this->state->scores['p2'] += $roundScores['p2'];

        // 3. Verifica condizione di vittoria (21 punti)
        if ($this->state->scores['p1'] >= 21 || $this->state->scores['p2'] >= 21) {
            $this->endGame();
            return;
        }

        // 4. Incrementa il contatore del round
        $this->state->roundIndex++;

        // 5. Resetta lo stato dei giocatori per il nuovo round
        $this->resetPlayersForNewRound();

        // 6. Inizializza il RNG con un seed deterministico basato sul seed della partita e il round
        $roundSeed = $this->gameSeed . '_round_' . $this->state->roundIndex;
        $this->initializeRNG($roundSeed);

        // 7. Crea un nuovo mazzo mescolato
        $this->createAndShuffleDeck();

        // 8. Metti 4 carte a terra
        $this->dealTableCards();

        // 9. Distribuisci le carte ai giocatori
        $this->distributeCards();

        // 10. Il turno ricomincia dal giocatore P1 (o potrebbe essere alternato)
        $this->state->currentTurnPlayer = 'p1';

        // 11. Resetta il tracking dell'ultimo giocatore che ha catturato
        $this->state->lastCapturePlayer = null;
    }

    /**
     * Calcola i punti per il round appena concluso
     * Ritorna un array con i punteggi: ['p1' => punti, 'p2' => punti]
     */
    private function calculateRoundScore(): array
    {
        return ScoreCalculator::calculateRoundScore($this->state->players);
    }

    /**
     * Termina la partita quando un giocatore raggiunge 21 punti
     *
     * TODO: Implementare la logica di fine partita:
     * - Segnare il vincitore
     * - Aggiornare lo stato isGameOver
     * - Eventualmente salvare statistiche finali
     */
    private function endGame(): void
    {
        $this->state->isGameOver = true;
        // TODO: Logica aggiuntiva di fine partita
    }

    /**
     * Resetta lo stato dei giocatori per preparare un nuovo round
     */
    private function resetPlayersForNewRound(): void
    {
        foreach ($this->state->players as $pid => &$playerData) {
            $playerData['hand'] = [];
            $playerData['captured'] = [];
            $playerData['scope'] = 0;
        }
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

    public function getBestBotAction(): string
    {
        $botId = 'p2';
        $botHand = $this->state->players[$botId]['hand'];
        $tableCards = $this->state->table;

        // 1. Cerca una presa
        foreach ($botHand as $cardInHand) {
            $valueInHand = GameConstants::getCardValue($cardInHand);
            
            // Cerca una carta singola da prendere
            foreach ($tableCards as $cardOnTable) {
                if ($valueInHand === GameConstants::getCardValue($cardOnTable)) {
                    return $cardInHand . 'x' . $cardOnTable;
                }
            }

            // Cerca una combinazione di carte da prendere (somma)
            // Questo è un esempio semplificato, per N carte la complessità aumenta.
            // Qui consideriamo solo 2 carte.
            if (count($tableCards) >= 2) {
                for ($i = 0; $i < count($tableCards); $i++) {
                    for ($j = $i + 1; $j < count($tableCards); $j++) {
                        if ($valueInHand === (GameConstants::getCardValue($tableCards[$i]) + GameConstants::getCardValue($tableCards[$j]))) {
                            return $cardInHand . 'x' . $tableCards[$i] . '+' . $tableCards[$j];
                        }
                    }
                }
            }
        }

        // 2. Se non ci sono prese, scarta una carta a caso
        $randomCard = $botHand[array_rand($botHand)];
        return $randomCard;
    }
}
