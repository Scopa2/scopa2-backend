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

    /** @var array<string, string> Carte diventate altre carte es. 4C => 4D per San Biagio */
    public array $mutations = [];

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
            // TODO: le mutazioni possono far visualizzare le carte dell'avversario,
            // ad esempio con San Biagio, quindi vanno gestite con attenzione.
            // Forse è meglio non esporle direttamente e applicarle solo alla vista del giocatore che le ha attivate?
            'mutations' => $this->mutations,
            'deck' => array_fill(0, count($this->deck), GameConstants::CARD_BACK),
            'players' => [],
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


    /**
     * Cambia il valore di una carta, ad esempio 3C => 1C, mantenendo il seme.
     * @param string $cardCode
     * @param string $newValue
     * @return void
     */
    public function mutateCardValue(string $cardCode, string $newValue): void
    {
        $effectiveCardCode = $this->getEffectiveCard($cardCode);
        $suit = substr($effectiveCardCode, -1);
        $mutatedCard = $newValue . $suit;
        $this->mutations[$cardCode] = $mutatedCard;
    }

    /**
     * Cambia il seme di una carta, ad esempio 3C => 3D, mantenendo il valore.
     * @param string $cardCode
     * @param string $newSuit
     * @return void
     */
    public function mutateCardSuit(string $cardCode, string $newSuit): void
    {
        $effectiveCardCode = $this->getEffectiveCard($cardCode);
        $value = substr($effectiveCardCode, 0, -1);
        $mutatedCard = $value . $newSuit;
        $this->mutations[$cardCode] = $mutatedCard;
    }

    /**
     * Cambia completamente una carta in un'altra, ad esempio 3C => 1D.
     * @param string $cardCode
     * @param string $newCardCode
     * @return void
     */
    public function mutateCard(string $cardCode, string $newCardCode): void
    {
        $this->mutations[$cardCode] = $newCardCode;
    }


    /**
     * Ritorna le carte catturate da un giocatore, applicando eventuali mutazioni.
     * @param string $pid
     * @return array
     */
    public function getEffectivePlayerCapturedCards(string $pid): array
    {
        $captured = $this->players->get($pid)->captured;
        $effectiveCaptured = [];

        foreach ($captured as $card) {
            if (isset($this->mutations[$card])) {
                $effectiveCaptured[] = $this->mutations[$card];
            } else {
                $effectiveCaptured[] = $card;
            }
        }

        return $effectiveCaptured;
    }

    /**
     * Ritorna le carte in mano a un giocatore, applicando eventuali mutazioni.
     * @param string $pid
     * @return array
     */
    public function getEffectivePlayerHandCards(string $pid): array
    {
        $hand = $this->players->get($pid)->hand;
        $effectiveHand = [];

        foreach ($hand as $card) {
            if (isset($this->mutations[$card])) {
                $effectiveHand[] = $this->mutations[$card];
            } else {
                $effectiveHand[] = $card;
            }
        }

        return $effectiveHand;
    }

    public function getEffectiveCard(string $cardCode): string
    {
        return $this->mutations[$cardCode] ?? $cardCode;
    }


}
