<?php

use App\Models\User;
use App\Services\MatchmakingService;
use Illuminate\Support\Facades\Redis;

beforeEach(fn () => Redis::flushall());

// --- Service-level tests ---

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

// --- HTTP endpoint tests ---

test('join matchmaking without auth returns 401', function () {
    $this->postJson('/api/matchmaking/join')->assertStatus(401);
});

test('leave matchmaking without auth returns 401', function () {
    $this->postJson('/api/matchmaking/leave')->assertStatus(401);
});

test('matchmaking status without auth returns 401', function () {
    $this->getJson('/api/matchmaking/status')->assertStatus(401);
});

test('authenticated user can join matchmaking queue', function () {
    [$user, $headers] = actingAsUser();

    $response = $this->withHeaders($headers)->postJson('/api/matchmaking/join');

    $response->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    // Verify in Redis
    expect(app(MatchmakingService::class)->isQueued($user))->toBeTrue();
});

test('joining queue twice returns already_queued', function () {
    [$user, $headers] = actingAsUser();

    $this->withHeaders($headers)->postJson('/api/matchmaking/join');
    $response = $this->withHeaders($headers)->postJson('/api/matchmaking/join');

    $response->assertStatus(200)
        ->assertJson(['status' => 'already_queued']);

    // Queue size should still be 1
    expect(app(MatchmakingService::class)->queueSize())->toBe(1);
});

test('authenticated user can leave the matchmaking queue', function () {
    [$user, $headers] = actingAsUser();
    $matchmaking = app(MatchmakingService::class);
    $matchmaking->enqueue($user);

    $response = $this->withHeaders($headers)->postJson('/api/matchmaking/leave');

    $response->assertStatus(200)
        ->assertJson(['status' => 'left']);

    expect($matchmaking->isQueued($user))->toBeFalse();
});

test('leaving queue when not in queue returns not_queued', function () {
    [$user, $headers] = actingAsUser();

    $response = $this->withHeaders($headers)->postJson('/api/matchmaking/leave');

    $response->assertStatus(200)
        ->assertJson(['status' => 'not_queued']);
});

test('status endpoint returns queued true when in queue', function () {
    [$user, $headers] = actingAsUser();
    app(MatchmakingService::class)->enqueue($user);

    $response = $this->withHeaders($headers)->getJson('/api/matchmaking/status');

    $response->assertStatus(200)
        ->assertJson(['queued' => true]);
});

test('status endpoint returns queued false when not in queue', function () {
    [, $headers] = actingAsUser();

    $response = $this->withHeaders($headers)->getJson('/api/matchmaking/status');

    $response->assertStatus(200)
        ->assertJson(['queued' => false]);
});

test('status endpoint returns correct queue size', function () {
    [$user1, $headers1] = actingAsUser();
    [$user2, $headers2] = actingAsUser();

    app(MatchmakingService::class)->enqueue($user1);
    app(MatchmakingService::class)->enqueue($user2);

    $response = $this->withHeaders($headers1)->getJson('/api/matchmaking/status');

    $response->assertStatus(200)
        ->assertJsonPath('queue_size', 2);
});
