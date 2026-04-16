<?php

use App\GameEngine\GameState;
use App\GameEngine\Santi\SantaCaterina;

test('SantaCaterina swaps p1 hand with p2 hand via mutations', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5D'];
    $state->players->p2->hand = ['7B', '1S'];

    SantaCaterina::apply('p1', $state);

    expect($state->getEffectivePlayerHandCards('p1'))->toBe(['7B', '1S'])
        ->and($state->getEffectivePlayerHandCards('p2'))->toBe(['3C', '5D']);
});

test('SantaCaterina called from p2 swaps in reverse direction', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5D'];
    $state->players->p2->hand = ['7B', '1S'];

    SantaCaterina::apply('p2', $state);

    // p2 becomes p1's effective hand, p1 becomes p2's effective hand
    expect($state->getEffectivePlayerHandCards('p2'))->toBe(['3C', '5D'])
        ->and($state->getEffectivePlayerHandCards('p1'))->toBe(['7B', '1S']);
});

test('SantaCaterina only swaps up to min hand size', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5D', '7S']; // 3 cards
    $state->players->p2->hand = ['7B', '1S'];        // 2 cards (smaller)

    SantaCaterina::apply('p1', $state);

    $p1Effective = $state->getEffectivePlayerHandCards('p1');
    $p2Effective = $state->getEffectivePlayerHandCards('p2');

    // First 2 cards swapped (min=2), third p1 card unchanged
    expect($p1Effective[0])->toBe('7B')
        ->and($p1Effective[1])->toBe('1S')
        ->and($p1Effective[2])->toBe('7S'); // unchanged, no mutation
    expect($p2Effective[0])->toBe('3C')
        ->and($p2Effective[1])->toBe('5D');
});

test('SantaCaterina creates mutations for both players cards', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C'];
    $state->players->p2->hand = ['7B'];

    SantaCaterina::apply('p1', $state);

    expect($state->mutations)->toHaveKey('3C')
        ->and($state->mutations)->toHaveKey('7B');
});

test('SantaCaterina has correct static properties', function () {
    expect(SantaCaterina::$id)->toBe('CAT')
        ->and(SantaCaterina::$cost)->toBe(3)
        ->and(SantaCaterina::$name)->toBeString();
});
