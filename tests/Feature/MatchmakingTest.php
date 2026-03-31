<?php

use App\Models\User;
use App\Services\MatchmakingService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushall();
});

it('can correctly check if a user is queued', function () {
    $user = User::factory()->create();
    $matchmaking = app(MatchmakingService::class);

    expect($matchmaking->isQueued($user))->toBeFalse();

    $matchmaking->enqueue($user);

    expect($matchmaking->isQueued($user))->toBeTrue();

    $matchmaking->dequeue($user);

    expect($matchmaking->isQueued($user))->toBeFalse();
});

it('can get the queue size', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $matchmaking = app(MatchmakingService::class);

    expect($matchmaking->queueSize())->toBe(0);

    $matchmaking->enqueue($user1);
    expect($matchmaking->queueSize())->toBe(1);

    $matchmaking->enqueue($user2);
    expect($matchmaking->queueSize())->toBe(2);

    $matchmaking->dequeue($user1);
    expect($matchmaking->queueSize())->toBe(1);
});
