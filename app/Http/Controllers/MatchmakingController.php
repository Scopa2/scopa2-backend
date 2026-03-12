<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinMatchmakingRequest;
use App\Services\MatchmakingService;
use Illuminate\Http\JsonResponse;

class MatchmakingController extends Controller
{
    public function __construct(private MatchmakingService $matchmaking) {}

    /**
     * Add the authenticated user to the matchmaking queue.
     */
    public function join(JoinMatchmakingRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($this->matchmaking->isQueued($user)) {
            return response()->json([
                'status' => 'already_queued',
                'message' => 'You are already in the matchmaking queue.',
            ]);
        }

        $this->matchmaking->enqueue($user);

        return response()->json([
            'status' => 'queued',
            'message' => 'You have been added to the matchmaking queue.',
        ]);
    }

    /**
     * Remove the authenticated user from the matchmaking queue.
     */
    public function leave(JoinMatchmakingRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->matchmaking->isQueued($user)) {
            return response()->json([
                'status' => 'not_queued',
                'message' => 'You are not in the matchmaking queue.',
            ]);
        }

        $this->matchmaking->dequeue($user);

        return response()->json([
            'status' => 'left',
            'message' => 'You have been removed from the matchmaking queue.',
        ]);
    }

    /**
     * Get the current matchmaking queue status for the authenticated user.
     */
    public function status(JoinMatchmakingRequest $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'queued' => $this->matchmaking->isQueued($user),
            'queue_size' => $this->matchmaking->queueSize(),
        ]);
    }
}
