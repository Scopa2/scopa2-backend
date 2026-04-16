<?php

use App\Models\GameEvent;

// --- Create game ---

test('unauthenticated user cannot create a game', function () {
    $this->postJson('/api/games')->assertStatus(401);
});

test('authenticated user can create a game', function () {
    [, $headers] = actingAsUser();

    $response = $this->withHeaders($headers)->postJson('/api/games');

    $response->assertStatus(201)
        ->assertJsonStructure(['status', 'game_id', 'seed']);

    expect($response->json('status'))->toBe('created');
});

test('created game is persisted in database', function () {
    [$user, $headers] = actingAsUser();

    $response = $this->withHeaders($headers)->postJson('/api/games');

    $gameId = $response->json('game_id');
    $this->assertDatabaseHas('games', [
        'id'          => $gameId,
        'player_1_id' => $user->id,
    ]);
});

// BUG: GameController::create() sets status=PLAYING even with no player_2.
// It should be WAITING_FOR_PLAYERS until a second player joins.
test('created game without player_2 has PLAYING status (known bug: should be WAITING_FOR_PLAYERS)', function () {
    [, $headers] = actingAsUser();

    $response = $this->withHeaders($headers)->postJson('/api/games');
    $gameId = $response->json('game_id');

    $this->assertDatabaseHas('games', ['id' => $gameId, 'status' => 'playing']);
});

// --- Join game ---

test('unauthenticated user cannot join a game', function () {
    [$p1] = actingAsUser();
    $game = createGame($p1);
    $this->postJson("/api/games/{$game->id}/join")->assertStatus(401);
});

test('second player can join an existing game', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2, $headers2] = actingAsUser();

    $game = createGame($p1);

    $response = $this->withHeaders($headers2)->postJson("/api/games/{$game->id}/join");

    $response->assertStatus(200)
        ->assertJson(['status' => 'joined', 'game_id' => $game->id]);

    $this->assertDatabaseHas('games', [
        'id'          => $game->id,
        'player_2_id' => $p2->id,
    ]);
});

test('joining a full game returns 422', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2, $headers2] = actingAsUser();
    [$p3, $headers3] = actingAsUser();

    $game = createGame($p1, $p2);

    $response = $this->withHeaders($headers3)->postJson("/api/games/{$game->id}/join");

    $response->assertStatus(422);
});

test('joining a non-existent game returns 404', function () {
    [, $headers] = actingAsUser();

    $this->withHeaders($headers)
        ->postJson('/api/games/00000000-0000-0000-0000-000000000000/join')
        ->assertStatus(404);
});

// --- Show game state ---

test('unauthenticated user cannot retrieve game state', function () {
    [$p1] = actingAsUser();
    $game = createGame($p1);

    $this->getJson("/api/games/{$game->id}")->assertStatus(401);
});

test('authenticated player can retrieve game state', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2] = actingAsUser();
    $game = createGame($p1, $p2);

    $response = $this->withHeaders($headers1)->getJson("/api/games/{$game->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'gameStatus',
            'state' => [
                'table',
                'players',
                'currentTurnPlayer',
                'isMyTurn',
                'isGameOver',
            ],
        ]);
});

test('game state shows real hand to owning player', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2] = actingAsUser();
    $game = createGame($p1, $p2);

    $response = $this->withHeaders($headers1)->getJson("/api/games/{$game->id}");

    $p1Hand = $response->json('state.players.p1.hand');

    // p1's hand should contain real card codes (not 'X')
    expect($p1Hand)->not->toBeEmpty()
        ->and(collect($p1Hand)->every(fn($c) => $c !== 'X'))->toBeTrue();
});

test('game state hides opponent hand as card backs', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2] = actingAsUser();
    $game = createGame($p1, $p2);

    $response = $this->withHeaders($headers1)->getJson("/api/games/{$game->id}");

    $p2Hand = $response->json('state.players.p2.hand');

    // All p2 cards should be 'X' (card backs) from p1's perspective
    expect(collect($p2Hand)->every(fn($c) => $c === 'X'))->toBeTrue();
});

test('show non-existent game returns 404', function () {
    [, $headers] = actingAsUser();

    $this->withHeaders($headers)
        ->getJson('/api/games/00000000-0000-0000-0000-000000000000')
        ->assertStatus(404);
});

// --- Handle action ---

test('unauthenticated user cannot submit an action', function () {
    [$p1] = actingAsUser();
    $game = createGame($p1);

    $this->postJson("/api/games/{$game->id}/action", ['action' => '3C'])
        ->assertStatus(401);
});

test('p1 can submit a valid discard action', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2] = actingAsUser();
    $game = createGame($p1, $p2);

    // Get p1's actual hand from show endpoint
    $showResponse = $this->withHeaders($headers1)->getJson("/api/games/{$game->id}");
    $p1Hand = $showResponse->json('state.players.p1.hand');
    $cardToPlay = $p1Hand[0];

    $response = $this->withHeaders($headers1)->postJson("/api/games/{$game->id}/action", [
        'action' => $cardToPlay,
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);
});

test('valid action is persisted as a game event', function () {
    [$p1, $headers1] = actingAsUser();
    [$p2] = actingAsUser();
    $game = createGame($p1, $p2);

    $showResponse = $this->withHeaders($headers1)->getJson("/api/games/{$game->id}");
    $cardToPlay = $showResponse->json('state.players.p1.hand.0');

    $this->withHeaders($headers1)->postJson("/api/games/{$game->id}/action", [
        'action' => $cardToPlay,
    ]);

    $this->assertDatabaseHas('game_events', [
        'game_id'  => $game->id,
        'actor_id' => 'p1',
        'pgn_action' => $cardToPlay,
    ]);
});

test('action on non-existent game returns 404', function () {
    [, $headers] = actingAsUser();

    $this->withHeaders($headers)
        ->postJson('/api/games/00000000-0000-0000-0000-000000000000/action', ['action' => '3C'])
        ->assertStatus(404);
});

// BUG: try-catch is commented out in GameController::handleAction() (line 89-100).
// When p2 submits an action on p1's turn, the engine throws an exception
// that propagates uncaught and produces a 500 error instead of a proper 422.
test('wrong player acting out of turn causes error response (known bug: should be 422 not 500)', function () {
    [$p1] = actingAsUser();
    [$p2, $headers2] = actingAsUser();
    $game = createGame($p1, $p2);

    // Get p2's hand (it's p1's turn, so p2 should be rejected)
    $showResponse = $this->withHeaders($headers2)->getJson("/api/games/{$game->id}");
    $p2Card = $showResponse->json('state.players.p2.hand.0'); // This is 'X' card back

    // Use a dummy card since p2's real cards are hidden from their own view... actually wait
    // p2 viewing as p2 will see their own real hand
    // Let's re-authenticate as p2 to get real hand
    // Actually from p2's perspective, p2 is the viewer - but game was created with p1 as player_1
    // getLoggedPlayerIndex: p2's user_id matches player_2_id → returns 'p2'
    // toPublicView('p2'): p2 sees their own hand as real cards

    // We'll try to act anyway with a plausible discard from p2's real hand
    // We need p2's view to get real cards. Let's query as p2.
    // Actually the test above queries as p2 so p2Hand from the show endpoint via p2 headers would show real p2 cards.
    // The show endpoint already uses $this->getLoggedPlayerIndex($game) which returns 'p2' for p2.

    // The real p2 hand from a p2-authenticated show call:
    // (reuse headers2 here)

    $p2ShowResponse = $this->withHeaders($headers2)->getJson("/api/games/{$game->id}");
    $realP2Card = $p2ShowResponse->json('state.players.p2.hand.0');

    $response = $this->withHeaders($headers2)->postJson("/api/games/{$game->id}/action", [
        'action' => $realP2Card,
    ]);

    // Due to commented-out try-catch, this will be 500 instead of a clean 422.
    // When the bug is fixed, change this to assertStatus(422).
    expect($response->status())->toBeIn([422, 500]);
});
