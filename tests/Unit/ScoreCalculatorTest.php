<?php

use App\GameEngine\GameState;
use App\GameEngine\ScoreCalculator;

// --- Scope ---

test('scope points added correctly', function () {
    $state = new GameState();
    $state->players->p1->scope = 2;
    $state->players->p2->scope = 1;

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['total'])->toBe(2)
        ->and($scores['p1']['scopaCount'])->toBe(2)
        ->and($scores['p2']['total'])->toBe(1)
        ->and($scores['p2']['scopaCount'])->toBe(1);
});

test('zero scope scores no points', function () {
    $state = new GameState();

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['total'])->toBe(0)
        ->and($scores['p2']['total'])->toBe(0);
});

// --- Settebello ---

test('p1 wins settebello when capturing 7D', function () {
    $state = new GameState();
    $state->players->p1->captured = ['7D', '3C'];
    $state->players->p2->captured = ['5S'];

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['settebello'])->toBeTrue()
        ->and($scores['p1']['total'])->toBeGreaterThanOrEqual(1)
        ->and($scores['p2']['settebello'])->toBeFalse();
});

test('nobody scores settebello when 7D not captured', function () {
    $state = new GameState();
    $state->players->p1->captured = ['7C'];
    $state->players->p2->captured = ['7B'];

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['settebello'])->toBeFalse()
        ->and($scores['p2']['settebello'])->toBeFalse();
});

// --- Allungo ---

test('p1 wins allungo with more cards', function () {
    $state = new GameState();
    $state->players->p1->captured = array_fill(0, 21, '3C');
    $state->players->p2->captured = array_fill(0, 19, '2D');

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['allungo'])->toBeTrue()
        ->and($scores['p2']['allungo'])->toBeFalse();
});

test('p2 wins allungo with more cards', function () {
    $state = new GameState();
    $state->players->p1->captured = array_fill(0, 15, '3C');
    $state->players->p2->captured = array_fill(0, 25, '2D');

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p2']['allungo'])->toBeTrue()
        ->and($scores['p1']['allungo'])->toBeFalse();
});

test('allungo is tied when both have same card count', function () {
    $state = new GameState();
    $state->players->p1->captured = array_fill(0, 20, '3C');
    $state->players->p2->captured = array_fill(0, 20, '2D');

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['allungo'])->toBeFalse()
        ->and($scores['p2']['allungo'])->toBeFalse();
});

// --- Denari ---

test('p1 wins denari with more gold cards', function () {
    $state = new GameState();
    $state->players->p1->captured = ['1D', '2D', '3D', '4D', '5D', '6D']; // 6 denari
    $state->players->p2->captured = ['7D', '8D', '9D'];                     // 3 denari

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['denari'])->toBeTrue()
        ->and($scores['p2']['denari'])->toBeFalse();
});

test('p2 wins denari with more gold cards', function () {
    $state = new GameState();
    $state->players->p1->captured = ['1D'];
    $state->players->p2->captured = ['2D', '3D', '4D'];

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p2']['denari'])->toBeTrue()
        ->and($scores['p1']['denari'])->toBeFalse();
});

test('denari tied when equal gold cards', function () {
    $state = new GameState();
    $state->players->p1->captured = ['1D', '2D'];
    $state->players->p2->captured = ['3D', '4D'];

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['denari'])->toBeFalse()
        ->and($scores['p2']['denari'])->toBeFalse();
});

// --- Primiera ---

test('p1 wins primiera with higher score across all suits', function () {
    $state = new GameState();
    // p1: 7 in each suit = 21+21+21+21 = 84
    $state->players->p1->captured = ['7D', '7C', '7S', '7B', '1D'];
    // p2: 6 in each suit = 18+18+18+18 = 72
    $state->players->p2->captured = ['6D', '6C', '6S', '6B'];

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['primiera'])->toBeTrue()
        ->and($scores['p2']['primiera'])->toBeFalse();
});

test('nobody wins primiera when a player is missing a suit', function () {
    $state = new GameState();
    // p1 has all 4 suits
    $state->players->p1->captured = ['7D', '7C', '7S', '7B'];
    // p2 missing Spade suit
    $state->players->p2->captured = ['6D', '6C', '6B'];

    $scores = ScoreCalculator::calculateRoundScore($state);

    // Current implementation: if either player lacks all suits, nobody wins
    expect($scores['p1']['primiera'])->toBeFalse()
        ->and($scores['p2']['primiera'])->toBeFalse();
});

test('nobody wins primiera when scores are equal', function () {
    $state = new GameState();
    $state->players->p1->captured = ['7D', '7C', '7S', '7B'];
    $state->players->p2->captured = ['7D', '7C', '7S', '7B']; // same primiera value

    $scores = ScoreCalculator::calculateRoundScore($state);

    expect($scores['p1']['primiera'])->toBeFalse()
        ->and($scores['p2']['primiera'])->toBeFalse();
});

// --- Combined total ---

test('total score accumulates all category wins', function () {
    $state = new GameState();

    // p1: scope=1, has 7D (settebello), more cards (allungo), more denari, all suits for primiera
    $state->players->p1->scope = 1;
    $state->players->p1->captured = [
        '7D',              // settebello
        '1D', '2D', '3D', '4D', '5D', '6D', '8D', '9D', '10D', // denari (9+1settebello = 10 total D)
        '7C', '7S', '7B', // primiera support
    ]; // 13 cards

    // p2: no settebello, fewer cards, no denari, only 4 suits for primiera
    $state->players->p2->captured = ['1C', '2C', '3C', '4C']; // 4 cards, missing D/S/B for primiera

    $scores = ScoreCalculator::calculateRoundScore($state);

    // p1 scores: scope(1) + settebello(1) + allungo(1) + denari(1) = 4, primiera=null (p2 missing suits)
    expect($scores['p1']['total'])->toBe(4);
    expect($scores['p2']['total'])->toBe(0);
});
