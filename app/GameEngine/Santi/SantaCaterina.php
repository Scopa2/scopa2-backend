<?php

namespace App\GameEngine\Santi;

use App\GameEngine\GameConstants;
use App\GameEngine\GameState;

/*
 * LORE:
 * Lo scambio di cuore è una famosa esperienza mistica di Santa Caterina da Siena,
 * in cui Gesù le avrebbe tolto il cuore per sostituirlo col Suo, simboleggiando la totale unione spirituale,
 * la trasformazione interiore e l'amore ardente per Dio e per i peccatori, spesso raffigurata nell'arte barocca.
 */

class SantaCaterina extends Santo
{
    public static ?string $id = "CAT";

    public static ?string $name = "Santa Caterina";

    public static ?string $description = "Scambia la tua mano con quella del tuo avversario";

    public static function apply(string $pid, GameState $state, array $params = []): void
    {
        $opponentId = $pid === 'p1' ? 'p2' : 'p1';
        $playerHand = $state->players->get($pid)->hand;
        $opponentHand = $state->players->get($opponentId)->hand;
        $minHandSize = min(count($playerHand), count($opponentHand));

        $playerEffectiveHand = $state->getEffectivePlayerHandCards($pid);
        $opponentEffectiveHand = $state->getEffectivePlayerHandCards($opponentId);

        for ($i = 0; $i < $minHandSize; $i++) {
            $state->mutateCard($playerHand[$i], $opponentEffectiveHand[$i]);
            $state->mutateCard($opponentHand[$i], $playerEffectiveHand[$i]);
        }

    }
}
