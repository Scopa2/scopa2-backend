<?php

use App\GameEngine\PlayerState;
use App\GameEngine\GameConstants;

// --- Hand management ---

test('addToHand adds card to hand', function () {
    $player = new PlayerState();
    $player->addToHand('7D');

    expect($player->hand)->toContain('7D')
        ->and($player->hand)->toHaveCount(1);
});

test('hasCardInHand returns true when card present', function () {
    $player = new PlayerState(hand: ['3C', '7D']);

    expect($player->hasCardInHand('7D'))->toBeTrue();
});

test('hasCardInHand returns false when card absent', function () {
    $player = new PlayerState(hand: ['3C']);

    expect($player->hasCardInHand('7D'))->toBeFalse();
});

test('removeFromHand removes card and returns true', function () {
    $player = new PlayerState(hand: ['3C', '7D', '5S']);
    $result = $player->removeFromHand('7D');

    expect($result)->toBeTrue()
        ->and($player->hand)->not->toContain('7D')
        ->and($player->hand)->toHaveCount(2);
});

test('removeFromHand returns false when card not in hand', function () {
    $player = new PlayerState(hand: ['3C']);
    $result = $player->removeFromHand('7D');

    expect($result)->toBeFalse()
        ->and($player->hand)->toHaveCount(1);
});

test('removeFromHand only removes one copy when duplicates exist', function () {
    $player = new PlayerState(hand: ['3C', '3C']);
    $player->removeFromHand('3C');

    expect($player->hand)->toHaveCount(1);
});

// --- Captured cards ---

test('addToCaptured adds card to captured', function () {
    $player = new PlayerState();
    $player->addToCaptured('7D');

    expect($player->captured)->toContain('7D');
});

test('hasCardCaptured returns true when captured', function () {
    $player = new PlayerState(captured: ['7D']);

    expect($player->hasCardCaptured('7D'))->toBeTrue();
});

test('hasCardCaptured returns false when not captured', function () {
    $player = new PlayerState();

    expect($player->hasCardCaptured('7D'))->toBeFalse();
});

test('removeFromCaptured removes the card', function () {
    $player = new PlayerState(captured: ['7D', '3C']);
    $player->removeFromCaptured('7D');

    expect($player->captured)->not->toContain('7D')
        ->and($player->captured)->toContain('3C');
});

test('removeFromCaptured does nothing when card absent', function () {
    $player = new PlayerState(captured: ['3C']);
    $player->removeFromCaptured('7D'); // no exception

    expect($player->captured)->toHaveCount(1);
});

// --- Blood ---

test('addBlood increases blood', function () {
    $player = new PlayerState(blood: 5);
    $player->addBlood(3);

    expect($player->blood)->toBe(8);
});

test('addBlood caps at MAX_BLOOD_PER_PLAYER', function () {
    $player = new PlayerState(blood: 18);
    $player->addBlood(10); // would be 28 but capped at 20

    expect($player->blood)->toBe(GameConstants::MAX_BLOOD_PER_PLAYER);
});

test('removeBlood decreases blood', function () {
    $player = new PlayerState(blood: 10);
    $player->removeBlood(4);

    expect($player->blood)->toBe(6);
});

test('removeBlood floors at zero', function () {
    $player = new PlayerState(blood: 3);
    $player->removeBlood(10);

    expect($player->blood)->toBe(0);
});

// --- Scope ---

test('incrementScope increases scope counter', function () {
    $player = new PlayerState();
    $player->incrementScope();
    $player->incrementScope();

    expect($player->scope)->toBe(2);
});

// --- Santi ---

test('addSanto adds santo id', function () {
    $player = new PlayerState();
    $player->addSanto('BIA');

    expect($player->santi)->toContain('BIA');
});

test('removeSanto removes santo id', function () {
    $player = new PlayerState(santi: ['BIA', 'PAN']);
    $player->removeSanto('BIA');

    expect($player->santi)->not->toContain('BIA')
        ->and($player->santi)->toContain('PAN');
});

// --- Reset ---

test('resetForNewRound clears hand, captured and scope', function () {
    $player = new PlayerState(
        hand: ['3C', '7D'],
        captured: ['5S'],
        blood: 8,
        santi: ['BIA'],
        scope: 3,
    );
    $player->resetForNewRound();

    expect($player->hand)->toBe([])
        ->and($player->captured)->toBe([])
        ->and($player->scope)->toBe(0);
});

test('resetForNewRound preserves blood and santi', function () {
    $player = new PlayerState(blood: 8, santi: ['BIA']);
    $player->resetForNewRound();

    expect($player->blood)->toBe(8)
        ->and($player->santi)->toContain('BIA');
});
