<?php

use App\GameEngine\GameState;
use App\GameEngine\GameConstants;
use App\GameEngine\GameUtilities;
use App\GameEngine\Santi\SanPantaleone;

test('SanPantaleone creates a mutation for each hand card', function () {
    mt_srand(42); // reproducible RNG
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S', '7B'];

    SanPantaleone::apply('p1', $state);

    // One mutation entry per card (suit mutation is overwritten by value mutation)
    expect($state->mutations)->toHaveKey('3C')
        ->and($state->mutations)->toHaveKey('5S')
        ->and($state->mutations)->toHaveKey('7B');
});

test('SanPantaleone mutated cards have valid suit', function () {
    mt_srand(42);
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S', '7B'];

    SanPantaleone::apply('p1', $state);

    foreach ($state->mutations as $original => $mutated) {
        $suit = GameUtilities::getCardSuit($mutated);
        expect(GameConstants::SUITS)->toContain($suit);
    }
});

test('SanPantaleone mutated cards have valid value', function () {
    mt_srand(42);
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S', '7B'];

    SanPantaleone::apply('p1', $state);

    foreach ($state->mutations as $original => $mutated) {
        $value = GameUtilities::getCardValue($mutated);
        expect(GameConstants::VALUES)->toContain($value);
    }
});

test('SanPantaleone does not affect opponent hand', function () {
    mt_srand(42);
    $state = new GameState();
    $state->players->p1->hand = ['3C'];
    $state->players->p2->hand = ['5S', '7B'];

    SanPantaleone::apply('p1', $state);

    expect($state->getEffectivePlayerHandCards('p2'))->toBe(['5S', '7B']);
});

test('SanPantaleone does not change the number of hand cards', function () {
    mt_srand(42);
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S', '7B'];

    SanPantaleone::apply('p1', $state);

    expect($state->players->p1->hand)->toHaveCount(3);
});

test('SanPantaleone has correct static properties', function () {
    expect(SanPantaleone::$id)->toBe('PAN')
        ->and(SanPantaleone::$cost)->toBe(3)
        ->and(SanPantaleone::$name)->toBeString();
});
