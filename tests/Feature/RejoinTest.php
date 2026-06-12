<?php

use App\Enums\GameStateEnum;
use App\Models\Game;

test('unauthenticated user cannot list active games', function () {
    $this->getJson('/api/me/active-games')->assertStatus(401);
});

test('user lists active games where they are p1', function () {
    [$p1, $headers] = actingAsUser();
    [$p2] = actingAsUser();

    $g = createGame($p1, $p2);

    $resp = $this->withHeaders($headers)->getJson('/api/me/active-games');

    $resp->assertOk();
    expect($resp->json('data'))->toHaveCount(1)
        ->and($resp->json('data.0.game_id'))->toBe($g->id)
        ->and($resp->json('data.0.my_index'))->toBe('p1')
        ->and($resp->json('data.0.opponent_id'))->toBe($p2->id);
});

test('user lists active games where they are p2', function () {
    [$p1] = actingAsUser();
    [$p2, $headers] = actingAsUser();

    $g = createGame($p1, $p2);

    $resp = $this->withHeaders($headers)->getJson('/api/me/active-games');

    $resp->assertOk();
    expect($resp->json('data'))->toHaveCount(1)
        ->and($resp->json('data.0.game_id'))->toBe($g->id)
        ->and($resp->json('data.0.my_index'))->toBe('p2');
});

test('user does not see other players games', function () {
    [$p1] = actingAsUser();
    [$p2] = actingAsUser();
    [$stranger, $strangerHeaders] = actingAsUser();

    createGame($p1, $p2);

    $resp = $this->withHeaders($strangerHeaders)->getJson('/api/me/active-games');
    $resp->assertOk();
    expect($resp->json('data'))->toBeEmpty();
});

test('user does not see finished games', function () {
    [$p1, $headers] = actingAsUser();
    [$p2] = actingAsUser();

    $g = createGame($p1, $p2);
    $g->status = GameStateEnum::FINISHED;
    $g->save();

    $resp = $this->withHeaders($headers)->getJson('/api/me/active-games');
    $resp->assertOk();
    expect($resp->json('data'))->toBeEmpty();
});

test('user can fetch state of in-progress game as p1', function () {
    [$p1, $headers] = actingAsUser();
    [$p2] = actingAsUser();

    $g = createGame($p1, $p2);

    $resp = $this->withHeaders($headers)->getJson("/api/games/{$g->id}");
    $resp->assertOk();
    expect($resp->json('state.players.p1.hand'))->toBeArray()
        ->and(count($resp->json('state.players.p1.hand')))->toBe(3);
});

test('user can fetch state of in-progress game as p2', function () {
    [$p1] = actingAsUser();
    [$p2, $headers] = actingAsUser();

    $g = createGame($p1, $p2);

    $resp = $this->withHeaders($headers)->getJson("/api/games/{$g->id}");
    $resp->assertOk();
    expect($resp->json('state.players.p2.hand'))->toBeArray()
        ->and(count($resp->json('state.players.p2.hand')))->toBe(3);
});

test('unrelated user cannot fetch game state', function () {
    [$p1] = actingAsUser();
    [$p2] = actingAsUser();
    [$stranger, $strangerHeaders] = actingAsUser();

    $g = createGame($p1, $p2);

    $resp = $this->withHeaders($strangerHeaders)->getJson("/api/games/{$g->id}");
    $resp->assertStatus(403);
});
