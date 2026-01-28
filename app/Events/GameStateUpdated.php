<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameId;
    public $stateP1; // Stato visto da P1
    public $stateP2; // Stato visto da P2

    public function __construct($gameId, $fullState)
    {
        $this->gameId = $gameId;
        // Pre-calcoliamo le viste mascherate
        $this->stateP1 = $fullState->toPublicView('p1'); // Assumendo ID fissi p1/p2 per ora
        $this->stateP2 = $fullState->toPublicView('p2');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->gameId),
        ];
    }
}
