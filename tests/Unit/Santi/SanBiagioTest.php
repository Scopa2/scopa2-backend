<?php

use App\GameEngine\GameState;
use App\GameEngine\Santi\SanBiagio;

test('SanBiagio transforms all p1 hand cards to Denari suit', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S', '7B'];
    $state->players->p2->hand = ['2D', '4D'];

    SanBiagio::apply('p1', $state);

    $effectiveHand = $state->getEffectivePlayerHandCards('p1');

    expect($effectiveHand)->toBe(['3D', '5D', '7D']);
});

test('SanBiagio preserves card values when changing suit', function () {
    $state = new GameState();
    $state->players->p1->hand = ['10C', '1S', '6B'];

    SanBiagio::apply('p1', $state);

    $effectiveHand = $state->getEffectivePlayerHandCards('p1');

    expect($effectiveHand)->toBe(['10D', '1D', '6D']);
});

test('SanBiagio does not affect opponent hand', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C'];
    $state->players->p2->hand = ['5S', '7B'];

    SanBiagio::apply('p1', $state);

    expect($state->getEffectivePlayerHandCards('p2'))->toBe(['5S', '7B']);
});

test('SanBiagio cards already in Denari suit remain Denari', function () {
    $state = new GameState();
    $state->players->p1->hand = ['7D'];

    SanBiagio::apply('p1', $state);

    expect($state->getEffectivePlayerHandCards('p1'))->toBe(['7D']);
});

test('SanBiagio creates a mutation entry per hand card', function () {
    $state = new GameState();
    $state->players->p1->hand = ['3C', '5S', '7B'];

    SanBiagio::apply('p1', $state);

    expect($state->mutations)->toHaveKey('3C')
        ->and($state->mutations)->toHaveKey('5S')
        ->and($state->mutations)->toHaveKey('7B');
});

test('SanBiagio has correct static properties', function () {
    expect(SanBiagio::$id)->toBe('BIA')
        ->and(SanBiagio::$cost)->toBe(3)
        ->and(SanBiagio::$name)->toBeString()
        ->and(SanBiagio::$description)->toBeString();
});
