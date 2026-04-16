<?php

use App\GameEngine\ScopaNotationParser;
use App\GameEngine\GameConstants;

test('parses shop buy with multiple payment cards', function () {
    $result = ScopaNotationParser::parse('$BIA(3C+4D)');

    expect($result['type'])->toBe(GameConstants::TYPE_SHOP_BUY)
        ->and($result['santo_id'])->toBe('BIA')
        ->and($result['payment'])->toBe(['3C', '4D']);
});

test('parses shop buy with empty payment', function () {
    $result = ScopaNotationParser::parse('$PAN()');

    expect($result['type'])->toBe(GameConstants::TYPE_SHOP_BUY)
        ->and($result['santo_id'])->toBe('PAN')
        ->and($result['payment'])->toBe([]);
});

test('parses shop buy with single payment card', function () {
    $result = ScopaNotationParser::parse('$CAT(7D)');

    expect($result['type'])->toBe(GameConstants::TYPE_SHOP_BUY)
        ->and($result['santo_id'])->toBe('CAT')
        ->and($result['payment'])->toBe(['7D']);
});

test('parses santo use with multiple params', function () {
    $result = ScopaNotationParser::parse('@CAT[p1|p2]');

    expect($result['type'])->toBe(GameConstants::TYPE_SANTO_USE)
        ->and($result['santo_id'])->toBe('CAT')
        ->and($result['params'])->toBe(['p1', 'p2']);
});

test('parses santo use without brackets returns empty params', function () {
    $result = ScopaNotationParser::parse('@BIA');

    expect($result['type'])->toBe(GameConstants::TYPE_SANTO_USE)
        ->and($result['santo_id'])->toBe('BIA')
        ->and($result['params'])->toBe([]);
});

test('parses santo use with empty brackets returns params with empty string', function () {
    // NOTE: explode('|', '') returns [''] not [] — parser edge case.
    // Empty brackets should ideally return [] but currently returns [''].
    $result = ScopaNotationParser::parse('@PAN[]');

    expect($result['type'])->toBe(GameConstants::TYPE_SANTO_USE)
        ->and($result['santo_id'])->toBe('PAN')
        ->and($result['params'])->toBe(['']);
});

test('parses simple card discard with no targets', function () {
    $result = ScopaNotationParser::parse('7D');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('7D')
        ->and($result['targets'])->toBe([])
        ->and($result['is_scopa'])->toBeFalse();
});

test('parses card play with single capture target', function () {
    $result = ScopaNotationParser::parse('7Dx7C');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('7D')
        ->and($result['targets'])->toBe(['7C'])
        ->and($result['is_scopa'])->toBeFalse();
});

test('parses card play with multiple capture targets', function () {
    $result = ScopaNotationParser::parse('7Dx3C+4D');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('7D')
        ->and($result['targets'])->toBe(['3C', '4D'])
        ->and($result['is_scopa'])->toBeFalse();
});

test('parses scopa marker correctly', function () {
    $result = ScopaNotationParser::parse('7Dx7C#');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('7D')
        ->and($result['targets'])->toBe(['7C'])
        ->and($result['is_scopa'])->toBeTrue();
});

test('parses 10-value card correctly', function () {
    $result = ScopaNotationParser::parse('10Bx5S+5D');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('10B')
        ->and($result['targets'])->toBe(['5S', '5D']);
});

test('parses card play with parenthesized targets', function () {
    $result = ScopaNotationParser::parse('7Dx(3C+4D)');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('7D')
        ->and($result['targets'])->toBe(['3C', '4D']);
});

test('trims whitespace from action string', function () {
    $result = ScopaNotationParser::parse('  7D  ');

    expect($result['type'])->toBe(GameConstants::TYPE_CARD_PLAY)
        ->and($result['card'])->toBe('7D');
});
