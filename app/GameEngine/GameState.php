<?php

namespace App\GameEngine;

use App\GameEngine\GameConstants;

/**
 * Rappresenta lo stato atomico della partita in una dato momento.
 * In un sistema distribuito, questo è l'oggetto che viene sincronizzato tra i nodi.
 */
class GameState
{
    // Stato del tavolo e mazzo
    public array $deck = [];
    public array $table = [];
    public array $shop = [];

    // Stato dei giocatori
    public array $players = [];
    public string $currentTurnPlayer = 'p1';
    public bool $isGameOver = false;

    // Metadati per la gestione dei round (Opzionali per PGN)
    public int $roundIndex = 1;

    // Punteggi totali dei giocatori
    public array $scores = [
        'p1' => 0,
        'p2' => 0
    ];

    // Ultimo giocatore che ha fatto una presa (per assegnare le carte rimaste)
    public ?string $lastCapturePlayer = null;

    public function __construct()
    {
        // Inizializzazione struttura dati giocatori
        $template = [
            'hand' => [],       // Carte correntemente in mano
            'captured' => [],   // Mazzo delle carte prese (per punti e shop)
            'santi' => [],      // Santini acquistati e pronti all'uso
            'blood' => 0,       // Sangue di San Gennaro (il "resto" dello shop)
            'scope' => 0        // Conteggio scope effettuate
        ];

        $this->players = [
            'p1' => $template,
            'p2' => $template
        ];
    }

    /**
     * Trasforma lo stato interno in una "Proiezione" pubblica.
     * Risolve il TypeError trasformando i dati per il trasporto (Marshaling).
     * * @param string $viewerId L'ID del giocatore che richiede la vista (p1 o p2)
     * @return array Lo stato sanificato per il client
     */
    public function toPublicView(string $viewerId): array
    {
        // Creiamo una rappresentazione array dello stato attuale
        // Questo evita crash di tipo perché non modifichiamo le proprietà della classe
        $publicState = [
            'table' => $this->table,
            'shop' => $this->shop,
            'currentTurnPlayer' => $this->currentTurnPlayer,
            'isGameOver' => $this->isGameOver,
            'roundIndex' => $this->roundIndex,
            'lastCapturePlayer' => $this->lastCapturePlayer,
            // Trasmettiamo il mazzo come array di placeholder 'X' della stessa lunghezza
            'deck' => array_fill(0, count($this->deck), GameConstants::CARD_BACK),
            'players' => []
        ];

        foreach ($this->players as $pid => $data) {
            $isOwner = true;// ($pid === $viewerId); //DEBUG

            // Logica di mascheramento (Information Hiding)
            $playerView = [
                'blood' => $data['blood'],
                'scope' => $data['scope'],
                'santi' => $data['santi'], // I santi posseduti sono solitamente visibili
                'captured_count' => count($data['captured']),
                'totalScore' => $this->scores[$pid], // Punteggio totale del giocatore
            ];

            // Gestione Mano
            if ($isOwner) {
                // Se sono io, vedo le mie carte
                $playerView['hand'] = $data['hand'];
                // Vedo anche esattamente cosa ho preso (per decidere cosa sacrificare allo shop)
                $playerView['captured'] = $data['captured'];
            } else {
                // Se è l'avversario, vedo solo quante carte ha, rappresentate come placeholder
                $count = count($data['hand']);
                $playerView['hand'] = array_fill(0, $count, GameConstants::CARD_BACK);

                // Per le carte prese (captured) mostriamo lo stesso numero di placeholder
                // così che i client possano trattarle come una collezione di slot modificabili.
                $playerView['captured'] = $data['captured'];
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
        return in_array($cardCode, $this->players[$pid]['hand']);
    }

    /**
     * Utility per verificare se un giocatore ha una carta tra le prese (per lo shop)
     */
    public function playerHasCaptured(string $pid, string $cardCode): bool
    {
        return in_array($cardCode, $this->players[$pid]['captured']);
    }
}
