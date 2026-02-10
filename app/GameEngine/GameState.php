<?php

namespace App\GameEngine;

use App\GameEngine\GameConstants;
use App\GameEngine\Santi\Santo;

/**
 * Rappresenta lo stato atomico della partita in una dato momento.
 * In un sistema distribuito, questo è l'oggetto che viene sincronizzato tra i nodi.
 */
class GameState
{
    /** @var array<string> Carte nel mazzo */
    public array $deck = [];

    /** @var array<string> Carte sul tavolo */
    public array $table = [];

    /** @var array<array> Santi disponibili nello shop */
    public array $shop = [];

    /** Stato dei giocatori */
    public Players $players;

    public string $currentTurnPlayer = 'p1';
    public bool $isGameOver = false;

    public int $roundIndex = 1;
    public int $turnIndex = 1;

    /** Punteggi totali dei giocatori */
    public GameScores $scores;

    /** Ultimo giocatore che ha fatto una presa (per assegnare le carte rimaste) */
    public ?string $lastCapturePlayer = null;

    /** Ultima mossa PGN (per animazioni client) */
    public ?string $lastMovePgn = null;

    public function __construct()
    {
        $this->players = new Players();
        $this->scores = new GameScores();
    }

    /**
     * Trasforma lo stato interno in una "Proiezione" pubblica.
     * Risolve il TypeError trasformando i dati per il trasporto (Marshaling).
     *
     * @param string $viewerId L'ID del giocatore che richiede la vista (p1 o p2)
     * @return array Lo stato sanificato per il client
     */
    public function toPublicView(string $viewerId): array
    {
        $publicState = [
            'table' => $this->table,
            'shop' => $this->shop,
            'currentTurnPlayer' => $this->currentTurnPlayer,
            'isMyTurn' => ($this->currentTurnPlayer === $viewerId),
            'isGameOver' => $this->isGameOver,
            'roundIndex' => $this->roundIndex,
            'turnIndex' => $this->turnIndex,
            'lastCapturePlayer' => $this->lastCapturePlayer,
            'lastMovePgn' => $this->lastMovePgn,
            'deck' => array_fill(0, count($this->deck), GameConstants::CARD_BACK),
            'players' => []
        ];

        foreach ($this->players->all() as $pid => $playerState) {
            $isOwner = ($pid === $viewerId);

            $playerView = [
                'blood' => $playerState->blood,
                'scope' => $playerState->scope,
                'santi' => $playerState->santi,
                'totalScore' => $this->scores->getScore($pid),
            ];

            if ($isOwner) {
                $playerView['hand'] = $playerState->hand;
                $playerView['captured'] = $playerState->captured;
            } else {
                $count = count($playerState->hand);
                $playerView['hand'] = array_fill(0, $count, GameConstants::CARD_BACK);
                $playerView['captured'] = $playerState->captured;
            }

            $publicState['players'][$pid] = $playerView;
        }

        return $publicState;
    }

    /**
     * Utility per verificare se un giocatore possiede una carta
     */
    public function playerHasCard(string $pid, string $cardCode): bool
    {
        return $this->players->get($pid)->hasCardInHand($cardCode);
    }

    /**
     * Utility per verificare se un giocatore ha una carta tra le prese (per lo shop)
     */
    public function playerHasCaptured(string $pid, string $cardCode): bool
    {
        return $this->players->get($pid)->hasCardCaptured($cardCode);
    }
}
