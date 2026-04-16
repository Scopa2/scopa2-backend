<?php

use App\GameEngine\GameState;
use App\GameEngine\GameConstants;

// --- toPublicView ---

test('toPublicView exposes own hand to correct viewer', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '7D', '5S'];
    $state->players->p2->hand = ['2D', '4B', '1C'];

    $view = $state->toPublicView('p1');

    expect($view['players']['p1']['hand'])->toBe(['3C', '7D', '5S']);
});

test('toPublicView hides opponent hand as card backs', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '7D', '5S'];
    $state->players->p2->hand = ['2D', '4B'];

    $view = $state->toPublicView('p1');

    // p2 hand should be replaced with X placeholders
    expect($view['players']['p2']['hand'])->toHaveCount(2)
        ->and($view['players']['p2']['hand'])->each->toBe(GameConstants::CARD_BACK);
});

test('toPublicView hidden hand count matches real hand count', function () {
    $state = new GameState();
    $state->players->p2->hand = ['1D', '2D', '3D'];

    $view = $state->toPublicView('p1');

    expect($view['players']['p2']['hand'])->toHaveCount(3);
});

test('toPublicView exposes own captured cards', function () {
    $state = new GameState();
    $state->players->p1->captured = ['7D', '3C'];

    $view = $state->toPublicView('p1');

    expect($view['players']['p1']['captured'])->toBe(['7D', '3C']);
});

test('toPublicView exposes opponent captured cards', function () {
    $state = new GameState();
    $state->players->p2->captured = ['7D'];

    $view = $state->toPublicView('p1');

    // Captured cards are visible for both players
    expect($view['players']['p2']['captured'])->toBe(['7D']);
});

test('toPublicView exposes deck as card backs with correct count', function () {
    $state = new GameState();
    $state->deck = ['1D', '2D', '3D', '4D', '5D'];

    $view = $state->toPublicView('p1');

    expect($view['deck'])->toHaveCount(5)
        ->and($view['deck'])->each->toBe(GameConstants::CARD_BACK);
});

test('toPublicView sets isMyTurn correctly for p1', function () {
    $state = new GameState();
    $state->currentTurnPlayer = 'p1';

    $viewP1 = $state->toPublicView('p1');
    $viewP2 = $state->toPublicView('p2');

    expect($viewP1['isMyTurn'])->toBeTrue()
        ->and($viewP2['isMyTurn'])->toBeFalse();
});

test('toPublicView includes game metadata', function () {
    $state = new GameState();
    $state->roundIndex = 2;
    $state->turnIndex = 5;
    $state->isGameOver = false;
    $state->table = ['7D', '3C'];
    $state->currentTurnPlayer = 'p2';

    $view = $state->toPublicView('p1');

    expect($view['roundIndex'])->toBe(2)
        ->and($view['turnIndex'])->toBe(5)
        ->and($view['isGameOver'])->toBeFalse()
        ->and($view['table'])->toBe(['7D', '3C'])
        ->and($view['currentTurnPlayer'])->toBe('p2');
});

// --- Card mutations ---

test('mutateCardSuit changes suit while preserving value', function () {
    $state = new GameState();
    $state->mutateCardSuit('3C', 'D');

    expect($state->getEffectiveCard('3C'))->toBe('3D');
});

test('mutateCardValue changes value while preserving suit', function () {
    $state = new GameState();
    $state->mutateCardValue('7D', '1');

    expect($state->getEffectiveCard('7D'))->toBe('1D');
});

test('mutateCard replaces card entirely', function () {
    $state = new GameState();
    $state->mutateCard('7D', '3C');

    expect($state->getEffectiveCard('7D'))->toBe('3C');
});

test('getEffectiveCard returns original when no mutation', function () {
    $state = new GameState();

    expect($state->getEffectiveCard('7D'))->toBe('7D');
});

test('getEffectivePlayerHandCards applies mutations', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S'];
    $state->mutateCardSuit('3C', 'D'); // 3C → 3D

    $effective = $state->getEffectivePlayerHandCards('p1');

    expect($effective)->toBe(['3D', '5S']);
});

test('getEffectivePlayerCapturedCards applies mutations', function () {
    $state = new GameState();
    $state->players->p1->captured = ['7D', '2C'];
    $state->mutateCard('7D', '1S'); // 7D → 1S

    $effective = $state->getEffectivePlayerCapturedCards('p1');

    expect($effective)->toBe(['1S', '2C']);
});

test('chained mutations: second mutate uses already-mutated card as base', function () {
    $state = new GameState();
    $state->mutateCardSuit('3C', 'D'); // 3C → 3D (suit changed)
    $state->mutateCardValue('3C', '7');  // effective is now 3D, value changed: 7D

    expect($state->getEffectiveCard('3C'))->toBe('7D');
});
