<?php

use App\GameEngine\ScopaEngine;
use App\GameEngine\GameConstants;
use App\GameEngine\GameUtilities;

// --- Initial state ---

test('engine deals 40 cards total on init', function () {
    $engine = new ScopaEngine('test_seed_init');
    $state = $engine->getState();

    $totalCards = count($state->deck)
        + count($state->table)
        + count($state->players->p1->hand)
        + count($state->players->p2->hand);

    expect($totalCards)->toBe(40);
});

test('engine deals 4 cards to table on init', function () {
    $engine = new ScopaEngine('test_seed_table');
    expect($engine->getState()->table)->toHaveCount(4);
});

test('engine deals 3 cards to each player on init', function () {
    $engine = new ScopaEngine('test_seed_hands');
    $state = $engine->getState();

    expect($state->players->p1->hand)->toHaveCount(3)
        ->and($state->players->p2->hand)->toHaveCount(3);
});

test('engine starts with p1 as current player', function () {
    $engine = new ScopaEngine('test_seed_turn');
    expect($engine->getState()->currentTurnPlayer)->toBe('p1');
});

test('engine starts with turnIndex 1', function () {
    $engine = new ScopaEngine('test_seed_turns');
    expect($engine->getState()->turnIndex)->toBe(1);
});

test('engine populates shop with 3 santi on init', function () {
    $engine = new ScopaEngine('test_seed_shop');
    expect($engine->getState()->shop)->toHaveCount(3);
});

// --- Discard ---

test('discarding a card removes it from hand and adds to table', function () {
    $engine = new ScopaEngine('test_seed_discard');
    $state = $engine->getState();
    $card = $state->players->p1->hand[0];
    $tableCountBefore = count($state->table);

    $engine->applyAction('p1', $card);

    expect($state->players->p1->hand)->not->toContain($card)
        ->and($state->table)->toContain($card)
        ->and($state->table)->toHaveCount($tableCountBefore + 1);
});

// --- Capture ---

test('capturing a card moves it to captured pile and removes from table', function () {
    $engine = new ScopaEngine('test_seed_cap');
    $state = $engine->getState();

    $captureAction = null;
    foreach ($state->players->p1->hand as $handCard) {
        $handValue = GameUtilities::getCardValue($handCard);
        foreach ($state->table as $tableCard) {
            if (GameUtilities::getCardValue($tableCard) === $handValue) {
                $captureAction = ['hand' => $handCard, 'table' => $tableCard];
                break 2;
            }
        }
    }

    if ($captureAction === null) {
        $this->markTestSkipped('No single-card capture opportunity in initial deal for this seed');
    }

    $engine->applyAction('p1', $captureAction['hand'] . 'x' . $captureAction['table']);

    expect($state->players->p1->captured)->toContain($captureAction['hand'])
        ->and($state->players->p1->captured)->toContain($captureAction['table'])
        ->and($state->table)->not->toContain($captureAction['table']);
});

// --- Turn toggling ---

test('engine updates lastMovePgn after action', function () {
    $engine = new ScopaEngine('test_seed_123');
    $state = $engine->getState();
    $card = $state->players->p1->hand[0];

    $engine->applyAction('p1', $card);

    expect($engine->getState()->lastMovePgn)->toBe($card);
});

test('engine updates lastMovePgn to last action after sequential moves', function () {
    $engine = new ScopaEngine('test_seed_replay');
    $state = $engine->getState();

    $card1 = $state->players->p1->hand[0];
    $engine->applyAction('p1', $card1);
    expect($engine->getState()->lastMovePgn)->toBe($card1);

    $card2 = $state->players->p2->hand[0];
    $engine->applyAction('p2', $card2);
    expect($engine->getState()->lastMovePgn)->toBe($card2);
});

test('engine increments turnIndex after each card play', function () {
    $engine = new ScopaEngine('test_seed_turns');
    $state = $engine->getState();

    expect($state->turnIndex)->toBe(1);

    $engine->applyAction('p1', $state->players->p1->hand[0]);
    expect($engine->getState()->turnIndex)->toBe(2);

    $engine->applyAction('p2', $engine->getState()->players->p2->hand[0]);
    expect($engine->getState()->turnIndex)->toBe(3);
});

test('engine toggles current player after card play', function () {
    $engine = new ScopaEngine('test_seed_toggle');
    $state = $engine->getState();

    expect($state->currentTurnPlayer)->toBe('p1');

    $engine->applyAction('p1', $state->players->p1->hand[0]);
    expect($engine->getState()->currentTurnPlayer)->toBe('p2');

    $engine->applyAction('p2', $engine->getState()->players->p2->hand[0]);
    expect($engine->getState()->currentTurnPlayer)->toBe('p1');
});

// --- Wrong turn ---

test('engine throws exception when wrong player acts', function () {
    $engine = new ScopaEngine('test_seed_wrong');
    $state = $engine->getState();

    expect(fn () => $engine->applyAction('p2', $state->players->p2->hand[0]))
        ->toThrow(Exception::class);
});

// --- Shop buy does not advance turn ---

test('shop buy does not toggle current player', function () {
    $engine = new ScopaEngine('test_seed_buy');
    $state = $engine->getState();

    // Give p1 enough captured cards to afford the cheapest santo (cost=3)
    $santoId = $state->shop[0]['id'];
    $state->players->p1->captured = ['7D', '7C', '7S']; // blood value well above 3
    $state->players->p1->blood = 10;

    $engine->applyAction('p1', "\${$santoId}(7D+7C+7S)");

    expect($engine->getState()->currentTurnPlayer)->toBe('p1');
});

// --- Replay ---

test('replay produces same state as live play with same actions', function () {
    $seed = 'test_seed_replay_det';

    $engineA = new ScopaEngine($seed);
    $stateA = $engineA->getState();
    $card1 = $stateA->players->p1->hand[0];
    $card2 = $stateA->players->p2->hand[0];
    $engineA->applyAction('p1', $card1);
    $engineA->applyAction('p2', $card2);

    $engineB = new ScopaEngine($seed);
    $event1 = (object)['actor_id' => 'p1', 'pgn_action' => $card1];
    $event2 = (object)['actor_id' => 'p2', 'pgn_action' => $card2];
    $engineB->replay([$event1, $event2]);

    expect($engineB->getState()->lastMovePgn)->toBe($engineA->getState()->lastMovePgn)
        ->and($engineB->getState()->turnIndex)->toBe($engineA->getState()->turnIndex)
        ->and($engineB->getState()->currentTurnPlayer)->toBe($engineA->getState()->currentTurnPlayer);
});

test('two engines with same seed produce identical initial state', function () {
    $engineA = new ScopaEngine('shared_seed');
    $engineB = new ScopaEngine('shared_seed');

    expect($engineA->getState()->table)->toBe($engineB->getState()->table)
        ->and($engineA->getState()->players->p1->hand)->toBe($engineB->getState()->players->p1->hand)
        ->and($engineA->getState()->players->p2->hand)->toBe($engineB->getState()->players->p2->hand);
});

test('different seeds produce different initial states', function () {
    $engineA = new ScopaEngine('seed_alpha');
    $engineB = new ScopaEngine('seed_beta');

    // It is astronomically unlikely two different seeds produce identical tables
    $sameTable = $engineA->getState()->table === $engineB->getState()->table;
    expect($sameTable)->toBeFalse();
});

// --- Round end ---

test('engine fires onRoundEnded callback after round completes', function () {
    $roundEndedFired = false;

    $engine = new ScopaEngine(
        'test_seed_round_end',
        function () use (&$roundEndedFired) { $roundEndedFired = true; },
        function () {}
    );
    $state = $engine->getState();

    // Play through entire round; try captures to ensure someone scores
    $maxIterations = 300;
    $i = 0;
    while (!$state->isGameOver && $state->roundIndex === 1 && $i < $maxIterations) {
        $pid = $state->currentTurnPlayer;
        $hand = $state->players->get($pid)->hand;
        if (empty($hand)) break;

        $handCard = $hand[0];
        $handValue = GameUtilities::getCardValue($handCard);
        $capturedCard = null;
        foreach ($state->table as $tableCard) {
            if (GameUtilities::getCardValue($tableCard) === $handValue) {
                $capturedCard = $tableCard;
                break;
            }
        }

        $action = $capturedCard ? $handCard . 'x' . $capturedCard : $handCard;
        $engine->applyAction($pid, $action);
        $i++;
    }

    expect($roundEndedFired || $state->isGameOver)->toBeTrue();
});

// --- Game end ---

test('engine sets isGameOver and fires onGameEnded when win score reached', function () {
    $gameEndedPayload = null;

    $engine = new ScopaEngine(
        'test_seed_game_over',
        function () {},
        function ($payload) use (&$gameEndedPayload) { $gameEndedPayload = $payload; }
    );
    $state = $engine->getState();

    // Play through rounds until game ends
    $maxIterations = 600;
    $i = 0;
    while (!$state->isGameOver && $i < $maxIterations) {
        $pid = $state->currentTurnPlayer;
        $hand = $state->players->get($pid)->hand;
        if (empty($hand)) break;

        $handCard = $hand[0];
        $handValue = GameUtilities::getCardValue($handCard);
        $capturedCard = null;
        foreach ($state->table as $tableCard) {
            if (GameUtilities::getCardValue($tableCard) === $handValue) {
                $capturedCard = $tableCard;
                break;
            }
        }

        $engine->applyAction($pid, $capturedCard ? $handCard . 'x' . $capturedCard : $handCard);
        $i++;
    }

    expect($state->isGameOver)->toBeTrue()
        ->and($gameEndedPayload)->not->toBeNull()
        ->and($gameEndedPayload)->toHaveKey('winner');
});

// --- Bot action ---

test('getBestBotAction returns a string on p2 turn', function () {
    $engine = new ScopaEngine('test_seed_bot');
    $state = $engine->getState();
    $engine->applyAction('p1', $state->players->p1->hand[0]);

    $botAction = $engine->getBestBotAction();

    expect($botAction)->toBeString()->not->toBeEmpty();
});

test('getBestBotAction returns a card p2 actually holds', function () {
    $engine = new ScopaEngine('test_seed_bot2');
    $state = $engine->getState();
    $engine->applyAction('p1', $state->players->p1->hand[0]);

    $botAction = $engine->getBestBotAction();
    $p2Hand = $engine->getState()->players->p2->hand;
    $playedCard = explode('x', $botAction)[0];

    expect($p2Hand)->toContain($playedCard);
});

// --- dumpState ---

test('dumpState returns a non-empty string', function () {
    $engine = new ScopaEngine('test_seed_dump');
    expect($engine->dumpState())->toBeString()->not->toBeEmpty();
});
