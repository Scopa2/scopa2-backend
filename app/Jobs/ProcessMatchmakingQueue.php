<?php

namespace App\Jobs;

use App\Services\MatchmakingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMatchmakingQueue implements ShouldQueue
{
    use Queueable;

    public function handle(MatchmakingService $matchmaking): void
    {
        $matchmaking->findMatches();
    }
}
