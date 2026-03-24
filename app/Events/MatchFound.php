<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFound implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    private string $player1Id;

    private string $player2Id;

    public string $gameId;

    public function __construct(string $gameId, string $player1Id, string $player2Id)
    {
        $this->gameId = $gameId;
        $this->player1Id = $player1Id;
        $this->player2Id = $player2Id;
    }

    public function broadcastAs(): string
    {
        return 'match_found';
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->player1Id.'_matchmaking_result'),
            new PrivateChannel($this->player2Id.'_matchmaking_result')
        ];
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
        ];
    }
}
