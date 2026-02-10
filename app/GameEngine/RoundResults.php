<?php

namespace App\GameEngine;

use App\GameEngine\GameConstants;

/**
 * Rappresenta lo stato atomico della partita in una dato momento.
 * In un sistema distribuito, questo è l'oggetto che viene sincronizzato tra i nodi.
 */
class RoundResults
{
    public string $lastCardsTaker;
}
