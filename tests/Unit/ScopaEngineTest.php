<?php

use App\GameEngine\ScopaEngine;
use App\GameEngine\GameConstants;

test('engine updates lastMovePgn after action', function () {
    $seed = 'test_seed_123';
    $engine = new ScopaEngine($seed);
    
    // Get initial state
    $state = $engine->getState();
    $p1Hand = $state->players['p1']['hand'];
    
    // Pick the first card from P1's hand
    $cardToPlay = $p1Hand[0];
    
    // Action string (simple discard)
    $pgnAction = $cardToPlay;
    
    // Apply action
    $engine->applyAction('p1', $pgnAction);
    
    // Check state
    $updatedState = $engine->getState();
    
    expect($updatedState->lastMovePgn)->toBe($pgnAction);
});

test('engine updates lastMovePgn after replay', function () {
    // This mocks the replay process by manually applying multiple actions
    // and checking if lastMovePgn reflects the LAST one.
    
    $seed = 'test_seed_replay';
    $engine = new ScopaEngine($seed);
    $state = $engine->getState();
    
    $card1 = $state->players['p1']['hand'][0];
    $action1 = $card1;
    
    $engine->applyAction('p1', $action1);
    expect($engine->getState()->lastMovePgn)->toBe($action1);
    
    // Now p2 turn
    $card2 = $state->players['p2']['hand'][0];
    $action2 = $card2;
    
    $engine->applyAction('p2', $action2);
    expect($engine->getState()->lastMovePgn)->toBe($action2);
});

test('engine increments turnIndex after each turn', function () {
    $seed = 'test_seed_turns';
    $engine = new ScopaEngine($seed);
    $state = $engine->getState();
    
    expect($state->turnIndex)->toBe(1);
    
    $card1 = $state->players['p1']['hand'][0];
    $engine->applyAction('p1', $card1);
    
    expect($engine->getState()->turnIndex)->toBe(2);
    
    $card2 = $state->players['p2']['hand'][0];
    $engine->applyAction('p2', $card2);
    
    expect($engine->getState()->turnIndex)->toBe(3);
});
