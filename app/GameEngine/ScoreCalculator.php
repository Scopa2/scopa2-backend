<?php

namespace App\GameEngine;

/**
 * Calcola i punteggi di un round di Scopa secondo le regole ufficiali
 */
class ScoreCalculator
{
    // Valori per il calcolo della Primiera
    private const PRIMIERA_VALUES = [
        7 => 21,
        6 => 18,
        1 => 16,  // Asso
        5 => 15,
        4 => 14,
        3 => 13,
        2 => 12,
        8 => 10,  // Fante
        9 => 10,  // Cavallo
        10 => 10, // Re
    ];

    /**
     * Calcola i punti per entrambi i giocatori alla fine di un round
     *
     * @param GameState $state Lo stato attuale del gioco, con le carte catturate dai giocatori
     * @return array Punteggi dettagliati per entrambi i giocatori
     */
    public static function calculateRoundScore(GameState $state): array
    {
        $template = [
            'settebello' => false,
            'primiera' => false,
            'scopaCount' => 0,
            'allungo' => false,
            'cardsCaptured' => 0,
            'denari' => false,
            'denariCount' => 0,
            'total' => 0
        ];

        $scores = [
            'p1' => $template,
            'p2' => $template
        ];

        // 1. Punti per le Scope
        foreach ($state->players as $playerKey => $player) {
            $scopaCount = $player['scope'] ?? 0;
            if ($scopaCount > 0) {
                $scores[$playerKey]['total'] += $scopaCount;
                $scores[$playerKey]['scopaCount'] = $scopaCount;
            }
        }

        // 2. Punti per i 7 di Denari (Settebello)
        foreach ($state->players as $playerKey => $player) {
            $settebelloCount = self::countSettebello($state->getEffectivePlayerCapturedCards($playerKey));
            if ($settebelloCount > 0) {
                $scores[$playerKey]['total'] += $settebelloCount;
                $scores[$playerKey]['settebello'] = true;
                $scores[$playerKey]['settebelloCount'] = $settebelloCount;
            }
        }

        // 3. Punto per chi ha più carte (Allungo)
        $allungoWinner = self::calculateAllungo($state->players->toArray());
        if ($allungoWinner !== null) {
            $scores[$allungoWinner]['total'] += 1;
            $scores[$allungoWinner]['allungo'] = true;
            $scores[$allungoWinner]['cardsCaptured'] = count($state->players->toArray()[$allungoWinner]['captured']);
        }

        // 4. Punto per chi ha più Denari
        $denariWinner = self::calculateDenari($state);
        if ($denariWinner !== null) {
            $scores[$denariWinner]['total'] += 1;
            $scores[$denariWinner]['denari'] = true;
            $scores[$denariWinner]['denariCount'] = self::countDenari($state->getEffectivePlayerCapturedCards($denariWinner));
        }

        // 5. Punto per la Primiera
        $primieraWinner = self::calculatePrimiera($state);
        if ($primieraWinner !== null) {
            $scores[$primieraWinner]['total'] += 1;
            $scores[$primieraWinner]['primiera'] = true;
        }

        return $scores;
    }

    /**
     * Conta quanti 7 di Denari ha catturato un giocatore
     * (potrebbero essere più di uno a causa dei Santi)
     */
    private static function countSettebello(array $captured): int
    {
        $count = 0;
        foreach ($captured as $card) {
            if ($card === '7D') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Calcola chi ha catturato più carte (Allungo)
     * Ritorna 'p1', 'p2' o null in caso di parità
     */
    private static function calculateAllungo(array $players): ?string
    {
        $p1Count = count($players['p1']['captured']);
        $p2Count = count($players['p2']['captured']);

        if ($p1Count > $p2Count) {
            return 'p1';
        } elseif ($p2Count > $p1Count) {
            return 'p2';
        }

        // Parità: nessuno prende il punto
        return null;
    }

    /**
     * Calcola chi ha più carte di Denari
     * Ritorna 'p1', 'p2' o null in caso di parità
     */
    private static function calculateDenari(GameState $state): ?string
    {
        $p1Denari = self::countDenari($state->getEffectivePlayerCapturedCards('p1'));
        $p2Denari = self::countDenari($state->getEffectivePlayerCapturedCards('p2'));

        if ($p1Denari > $p2Denari) {
            return 'p1';
        } elseif ($p2Denari > $p1Denari) {
            return 'p2';
        }

        // Parità: nessuno prende il punto
        return null;
    }

    /**
     * Conta quante carte di Denari ci sono in una pila
     */
    private static function countDenari(array $captured): int
    {
        $count = 0;
        foreach ($captured as $card) {
            // Le carte di denari terminano con 'D'
            if (substr($card, -1) === 'D') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Calcola chi vince la Primiera
     * Ritorna 'p1', 'p2' o null se uno dei due non ha tutte e 4 i semi
     */
    private static function calculatePrimiera(GameState $state): ?string
    {
        $p1Score = self::getPrimieraScore($state->getEffectivePlayerCapturedCards('p1'));
        $p2Score = self::getPrimieraScore($state->getEffectivePlayerCapturedCards('p2'));

        // Se uno dei due non ha tutte e 4 i semi, nessuno vince la primiera
        if ($p1Score === null || $p2Score === null) {
            return null;
        }

        if ($p1Score > $p2Score) {
            return 'p1';
        } elseif ($p2Score > $p1Score) {
            return 'p2';
        }

        // Parità: nessuno prende il punto
        return null;
    }

    /**
     * Calcola il punteggio Primiera di un giocatore
     * Ritorna il punteggio totale o null se non ha tutte e 4 i semi
     */
    private static function getPrimieraScore(array $captured): ?int
    {
        // Organizza le carte per seme
        $cardsBySuit = [
            'D' => [],
            'C' => [],
            'S' => [],
            'B' => []
        ];

        foreach ($captured as $card) {
            $suit = substr($card, -1);
            $value = (int) substr($card, 0, -1);

            if (isset($cardsBySuit[$suit])) {
                $cardsBySuit[$suit][] = $value;
            }
        }

        // Verifica che abbia almeno una carta per ogni seme
        foreach ($cardsBySuit as $suit => $cards) {
            if (empty($cards)) {
                // Non ha tutte e 4 i semi, non può fare primiera
                return null;
            }
        }

        // Calcola il punteggio scegliendo la carta migliore per ogni seme
        $totalScore = 0;
        foreach ($cardsBySuit as $suit => $cards) {
            $bestValue = 0;
            foreach ($cards as $cardValue) {
                $primieraValue = self::PRIMIERA_VALUES[$cardValue] ?? 0;
                $bestValue = max($bestValue, $primieraValue);
            }
            $totalScore += $bestValue;
        }

        return $totalScore;
    }
}

