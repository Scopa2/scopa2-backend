<?php
namespace App\Events;

use App\GameEngine\GameState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameFinished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $results;
    private $gameId;

    public function __construct($results, string $gameId)
    {
        $this->results = $results;
        $this->gameId = $gameId;
    }

    public function broadcastAs(): string
    {
        return 'game_finished';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game_' . $this->gameId),
        ];
    }
}
