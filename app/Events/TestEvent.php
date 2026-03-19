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

class TestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $test;

    public function __construct()
    {
        $this->test = "MAIZ";
    }

    public function broadcastAs(): string
    {
        return 'test';
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("test_channel")
        ];
    }
}
