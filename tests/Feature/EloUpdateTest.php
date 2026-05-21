<?php

use App\Events\GameFinished;
use App\Listeners\UpdateEloOnGameFinished;
use App\Models\Game;
use App\Services\EloService;

test('listener updates elo on game finished — p1 wins', function () {
    [$p1] = actingAsUser(['elo' => 1000]);
    [$p2] = actingAsUser(['elo' => 1000]);

    $game = createGame($p1, $p2);

    $event = new GameFinished(['winner' => 'p1'], $game->id);
    (new UpdateEloOnGameFinished(new EloService))->handle($event);

    $p1->refresh();
    $p2->refresh();
    expect($p1->elo)->toBe(1016)
        ->and($p2->elo)->toBe(984);
});

test('listener handles draw', function () {
    [$p1] = actingAsUser(['elo' => 1000]);
    [$p2] = actingAsUser(['elo' => 1000]);

    $game = createGame($p1, $p2);

    $event = new GameFinished(['winner' => null], $game->id);
    (new UpdateEloOnGameFinished(new EloService))->handle($event);

    $p1->refresh();
    $p2->refresh();
    expect($p1->elo)->toBe(1000)
        ->and($p2->elo)->toBe(1000);
});

test('listener does not crash on missing game', function () {
    $event = new GameFinished(['winner' => 'p1'], (string) \Illuminate\Support\Str::uuid());
    (new UpdateEloOnGameFinished(new EloService))->handle($event);
    expect(true)->toBeTrue();
});

test('listener skips when no player 2', function () {
    [$p1] = actingAsUser(['elo' => 1000]);

    $game = \App\Models\Game::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'player_1_id' => $p1->id,
        'player_2_id' => null,
        'seed' => 'noop',
        'status' => \App\Enums\GameStateEnum::PLAYING,
    ]);

    $event = new GameFinished(['winner' => 'p1'], $game->id);
    (new UpdateEloOnGameFinished(new EloService))->handle($event);

    $p1->refresh();
    expect($p1->elo)->toBe(1000);
});

test('listener wired via event dispatch', function () {
    [$p1] = actingAsUser(['elo' => 1500]);
    [$p2] = actingAsUser(['elo' => 1500]);
    $game = createGame($p1, $p2);

    event(new GameFinished(['winner' => 'p2'], $game->id));

    $p1->refresh();
    $p2->refresh();
    expect($p1->elo)->toBe(1484)
        ->and($p2->elo)->toBe(1516);
});

function pickLegalAction(\App\GameEngine\GameState $state): string
{
    $pid = $state->currentTurnPlayer;
    $hand = $state->players->get($pid)->hand;
    $table = $state->table;

    foreach ($hand as $hc) {
        $v = \App\GameEngine\GameUtilities::getCardValue($state->getEffectiveCard($hc));

        // Exact match — mandatory.
        foreach ($table as $tc) {
            if (\App\GameEngine\GameUtilities::getCardValue($state->getEffectiveCard($tc)) === $v) {
                $isScopa = (count($table) === 1);
                return $hc . 'x' . $tc . ($isScopa ? '#' : '');
            }
        }

        // Sum capture (2 cards).
        for ($i = 0; $i < count($table); $i++) {
            for ($j = $i + 1; $j < count($table); $j++) {
                $a = \App\GameEngine\GameUtilities::getCardValue($state->getEffectiveCard($table[$i]));
                $b = \App\GameEngine\GameUtilities::getCardValue($state->getEffectiveCard($table[$j]));
                if ($a + $b === $v) {
                    $isScopa = (count($table) === 2);
                    return $hc . 'x' . $table[$i] . '+' . $table[$j] . ($isScopa ? '#' : '');
                }
            }
        }
    }

    return $hand[0];
}

test('game result persisted on game over', function () {
    [$p1, $headers1] = actingAsUser(['elo' => 1000]);
    [$p2, $headers2] = actingAsUser(['elo' => 1000]);

    $game = createGame($p1, $p2);

    $headers = ['p1' => $headers1, 'p2' => $headers2];

    $shadow = new \App\GameEngine\ScopaEngine($game->seed);
    $safety = 500;
    while (!$shadow->getState()->isGameOver && $safety-- > 0) {
        $pid = $shadow->getState()->currentTurnPlayer;
        $action = pickLegalAction($shadow->getState());
        $resp = $this->withHeaders($headers[$pid])
            ->postJson("/api/games/{$game->id}/action", ['action' => $action]);
        if ($resp->status() !== 200) {
            $events = \App\Models\GameEvent::where('game_id', $game->id)->orderBy('sequence_number')->get(['sequence_number','actor_id','pgn_action']);
            dump(compact('pid', 'action'), $resp->json(), $shadow->getState()->currentTurnPlayer, $shadow->getState()->isGameOver, $events->toArray());
        }
        $resp->assertStatus(200);
        $shadow->applyAction($pid, $action);
    }

    $game->refresh();
    expect($game->status->value)->toBe('finished')
        ->and($game->winner_id)->not->toBeNull()
        ->and($game->final_score_p1)->not->toBeNull()
        ->and($game->final_score_p2)->not->toBeNull();

    // ELO must have changed for both players.
    $p1->refresh();
    $p2->refresh();
    expect($p1->elo + $p2->elo)->toBe(2000);
    expect($p1->elo)->not->toBe(1000);
});
