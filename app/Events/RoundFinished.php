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

class RoundFinished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $state;
    private $p1Id;
    private $p2Id;

    public function __construct($state, string $p1Id, string $p2Id)
    {
        $this->state = $state;
        $this->p1Id = $p1Id;
        $this->p2Id = $p2Id;
    }

    public function broadcastAs(): string
    {
        return 'round_finished';
    }

    public function broadcastOn(): array
    {
        return [
            new Channel($this->p1Id.'_games'),
            new Channel($this->p2Id.'_games'),
        ];
    }
}
