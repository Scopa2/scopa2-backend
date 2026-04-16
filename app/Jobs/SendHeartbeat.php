<?php

namespace App\Jobs;

use App\Events\GameStateUpdated;
use App\Events\HeartBeat;
use App\Services\MatchmakingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendHeartbeat implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        broadcast(new HeartBeat());
    }
}
