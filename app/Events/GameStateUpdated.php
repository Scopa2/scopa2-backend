<?php
namespace App\Events;

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

    public $state;

    public function __construct($gameStateView)
    {
        $this->state = $gameStateView;
    }

    public function broadcastAs(): string
    {
        return 'game_sate_updated';
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('games'),
        ];
//        return [
//            new Channel('game.' . $this->gameId),
//        ];
    }
}
