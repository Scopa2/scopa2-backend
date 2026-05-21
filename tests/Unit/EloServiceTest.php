<?php

use App\Services\EloService;

test('equal players p1 wins gives +16/-16', function () {
    $delta = (new EloService)->compute(1000, 1000, 1.0);
    expect($delta['a'])->toBe(16)
        ->and($delta['b'])->toBe(-16);
});

test('equal players draw gives zero delta', function () {
    $delta = (new EloService)->compute(1000, 1000, 0.5);
    expect($delta['a'])->toBe(0)
        ->and($delta['b'])->toBe(0);
});

test('equal players p1 loses gives -16/+16', function () {
    $delta = (new EloService)->compute(1000, 1000, 0.0);
    expect($delta['a'])->toBe(-16)
        ->and($delta['b'])->toBe(16);
});

test('lower elo beating higher gets bigger gain', function () {
    $deltaUnderdog = (new EloService)->compute(800, 1200, 1.0);
    $deltaEqual = (new EloService)->compute(1000, 1000, 1.0);
    expect($deltaUnderdog['a'])->toBeGreaterThan($deltaEqual['a']);
});

test('higher elo beating lower gets smaller gain', function () {
    $deltaFavorite = (new EloService)->compute(1200, 800, 1.0);
    $deltaEqual = (new EloService)->compute(1000, 1000, 1.0);
    expect($deltaFavorite['a'])->toBeLessThan($deltaEqual['a']);
});

test('symmetry: delta a + delta b equals zero', function () {
    foreach ([0.0, 0.5, 1.0] as $score) {
        $delta = (new EloService)->compute(1234, 987, $score);
        expect($delta['a'] + $delta['b'])->toBe(0);
    }
});
