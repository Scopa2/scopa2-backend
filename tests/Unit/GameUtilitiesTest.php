<?php

use App\GameEngine\GameUtilities;

// --- getCardValue ---

test('getCardValue extracts value from standard card', function () {
    expect(GameUtilities::getCardValue('7D'))->toBe(7);
});

test('getCardValue handles 10-value card', function () {
    expect(GameUtilities::getCardValue('10B'))->toBe(10);
});

test('getCardValue handles ace', function () {
    expect(GameUtilities::getCardValue('1D'))->toBe(1);
});

test('getCardValue handles all suits', function () {
    expect(GameUtilities::getCardValue('5D'))->toBe(5)
        ->and(GameUtilities::getCardValue('5C'))->toBe(5)
        ->and(GameUtilities::getCardValue('5S'))->toBe(5)
        ->and(GameUtilities::getCardValue('5B'))->toBe(5);
});

test('getCardValue returns 0 for single-char string', function () {
    expect(GameUtilities::getCardValue('D'))->toBe(0);
});

// --- getCardSuit ---

test('getCardSuit extracts Denari suit', function () {
    expect(GameUtilities::getCardSuit('7D'))->toBe('D');
});

test('getCardSuit extracts Bastoni suit', function () {
    expect(GameUtilities::getCardSuit('10B'))->toBe('B');
});

test('getCardSuit extracts all suits correctly', function () {
    expect(GameUtilities::getCardSuit('1D'))->toBe('D')
        ->and(GameUtilities::getCardSuit('2C'))->toBe('C')
        ->and(GameUtilities::getCardSuit('3S'))->toBe('S')
        ->and(GameUtilities::getCardSuit('4B'))->toBe('B');
});

test('getCardSuit returns empty string for single char', function () {
    expect(GameUtilities::getCardSuit('D'))->toBe('');
});

// --- getCardBloodValue ---

test('getCardBloodValue for ace of Denari is 14', function () {
    // ace=11, Denari bonus=3 → 14
    expect(GameUtilities::getCardBloodValue('1D'))->toBe(14);
});

test('getCardBloodValue for 7 of Bastoni is 7', function () {
    // 7 base, Bastoni bonus=0 → 7
    expect(GameUtilities::getCardBloodValue('7B'))->toBe(7);
});

test('getCardBloodValue for 2 of Coppe is 4', function () {
    // 2 base, Coppe bonus=2 → 4
    expect(GameUtilities::getCardBloodValue('2C'))->toBe(4);
});

test('getCardBloodValue for 5 of Spade is 6', function () {
    // 5 base, Spade bonus=1 → 6
    expect(GameUtilities::getCardBloodValue('5S'))->toBe(6);
});

test('getCardBloodValue for 10 of Denari is 13', function () {
    // 10 base, Denari bonus=3 → 13
    expect(GameUtilities::getCardBloodValue('10D'))->toBe(13);
});

test('getCardBloodValue ace treated as 11 not 1', function () {
    // Ace has special value 11 in blood calculation
    $aceBlood = GameUtilities::getCardBloodValue('1B'); // 11+0=11
    $twoBlood = GameUtilities::getCardBloodValue('2B'); // 2+0=2
    expect($aceBlood)->toBeGreaterThan($twoBlood);
});
