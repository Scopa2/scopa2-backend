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

class GameStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $viewerId;
    public $state;

    public function __construct($state, string $viewerId)
    {
        $this->viewerId = $viewerId;
        $this->state = $state;
    }

    public function broadcastAs(): string
    {
        return 'game_state_updated';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->viewerId.'_games'),
        ];
//        return [
//            new Channel('game.' . $this->gameId),
//        ];
    }
}
