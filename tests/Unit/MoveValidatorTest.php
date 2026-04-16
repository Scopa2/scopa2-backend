<?php

use App\Enums\GameStateEnum;
use App\GameEngine\GameUtilities;
use App\GameEngine\ScopaEngine;
use App\GameEngine\Validators\MoveValidator;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

const VALIDATOR_SEED = 'validator_seed_fixed';

function makeGame(User $p1, User $p2): Game
{
    return Game::create([
        'id'          => (string) Str::uuid(),
        'player_1_id' => $p1->id,
        'player_2_id' => $p2->id,
        'seed'        => VALIDATOR_SEED,
        'status'      => GameStateEnum::PLAYING,
    ]);
}

beforeEach(function () {
    $this->p1 = User::factory()->create();
    $this->p2 = User::factory()->create();
    $this->game = makeGame($this->p1, $this->p2);

    // Read initial engine state so tests can use real cards
    $engine = new ScopaEngine(VALIDATOR_SEED);
    $this->initialState = $engine->getState();
    $this->p1Hand = $this->initialState->players->p1->hand;
    $this->tableCards = $this->initialState->table;
});

test('valid discard returns true', function () {
    $validator = new MoveValidator($this->game->id, 'p1');
    $result = $validator->validate($this->p1Hand[0]);

    expect($result)->toBeTrue()
        ->and($validator->getErrors())->toBeEmpty();
});

test('wrong player turn returns false with error', function () {
    // It is p1's turn; p2 tries to move
    $validator = new MoveValidator($this->game->id, 'p2');
    $result = $validator->validate($this->p1Hand[0]);

    expect($result)->toBeFalse()
        ->and($validator->getErrors())->not->toBeEmpty();
});

test('card not in hand returns false with error', function () {
    // Use a card string that cannot be in p1's 3-card hand
    $notInHand = 'FAKE_CARD';

    $validator = new MoveValidator($this->game->id, 'p1');
    $result = $validator->validate($notInHand);

    expect($result)->toBeFalse()
        ->and($validator->getErrors())->not->toBeEmpty();
});

test('valid capture where values match returns true', function () {
    // Find a capture opportunity in the initial deal
    $captureAction = null;
    foreach ($this->p1Hand as $handCard) {
        $handValue = GameUtilities::getCardValue($handCard);
        foreach ($this->tableCards as $tableCard) {
            if (GameUtilities::getCardValue($tableCard) === $handValue) {
                $captureAction = $handCard . 'x' . $tableCard;
                break 2;
            }
        }
    }

    if ($captureAction === null) {
        $this->markTestSkipped('No single-card capture in initial deal for this seed');
    }

    $validator = new MoveValidator($this->game->id, 'p1');
    expect($validator->validate($captureAction))->toBeTrue();
});

test('capture with wrong sum returns false', function () {
    // Play p1's first card but pretend to capture a table card with a different value
    $handCard = $this->p1Hand[0];
    $handValue = GameUtilities::getCardValue($handCard);

    // Find a table card with a DIFFERENT value
    $mismatchedTableCard = null;
    foreach ($this->tableCards as $tableCard) {
        if (GameUtilities::getCardValue($tableCard) !== $handValue) {
            $mismatchedTableCard = $tableCard;
            break;
        }
    }

    if ($mismatchedTableCard === null) {
        $this->markTestSkipped('All table cards match p1 hand card value for this seed');
    }

    $validator = new MoveValidator($this->game->id, 'p1');
    $result = $validator->validate($handCard . 'x' . $mismatchedTableCard);

    expect($result)->toBeFalse();
});

test('capture of card not on table returns false', function () {
    $handCard = $this->p1Hand[0];
    // Use a card that is in p1's own hand (definitely not on table)
    $notOnTable = $this->p1Hand[1] ?? '9B'; // fallback

    $validator = new MoveValidator($this->game->id, 'p1');
    $result = $validator->validate($handCard . 'x' . $notOnTable);

    expect($result)->toBeFalse();
});

test('getFirstError returns null when no errors', function () {
    $validator = new MoveValidator($this->game->id, 'p1');
    $validator->validate($this->p1Hand[0]);

    expect($validator->getFirstError())->toBeNull();
});

test('getFirstError returns string when error exists', function () {
    $validator = new MoveValidator($this->game->id, 'p2'); // wrong turn
    $validator->validate($this->p1Hand[0]);

    expect($validator->getFirstError())->toBeString();
});
