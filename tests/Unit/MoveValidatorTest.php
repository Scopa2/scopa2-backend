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

test('scopa flag accepted (server-side normalizes)', function () {
    // Validator no longer enforces flag — controller normalizes before persist.
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->table = ['7D'];
    $state->players->p1->hand = ['7C', '3B', '2S'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('7Cx7D'))->toBeTrue();
    expect($validator->validate('7Cx7D#'))->toBeTrue();
});

test('exact match wins over sum capture', function () {
    // Played 7; table has 7D AND 3C+4B (sum 7). Must take 7D alone.
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->table = ['7D', '3C', '4B'];
    $state->players->p1->hand = ['7S', '2D', '1B'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');

    // Sum capture (3C+4B) when 7D available — must reject.
    expect($validator->validate('7Sx3C+4B'))->toBeFalse()
        ->and($validator->getFirstError())->toContain('Exact match');

    // Exact capture — accepted.
    expect($validator->validate('7Sx7D'))->toBeTrue();
});

test('sum capture allowed when no exact match', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->table = ['3C', '4B', '2S'];
    $state->players->p1->hand = ['7D', '5C', '1B'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('7Dx3C+4B'))->toBeTrue();
});

test('duplicate capture targets rejected', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->table = ['3C', '4B'];
    $state->players->p1->hand = ['6D'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('6Dx3C+3C'))->toBeFalse();
});

test('buy rejects unknown santo', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('$XYZ()'))->toBeFalse()
        ->and($validator->getFirstError())->toContain('Unknown santo');
});

test('buy rejects santo not in shop', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->shop = [
        ['id' => 'BIA', 'name' => 'San Biagio', 'description' => '', 'cost' => 3, 'expiry' => 3],
    ];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('$PAN()'))->toBeFalse()
        ->and($validator->getFirstError())->toContain('not available');
});

test('buy rejects payment card not in captured', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->shop = [
        ['id' => 'BIA', 'name' => 'San Biagio', 'description' => '', 'cost' => 3, 'expiry' => 3],
    ];
    $state->players->p1->captured = ['3C'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('$BIA(7D)'))->toBeFalse()
        ->and($validator->getFirstError())->toContain('not in captured');
});

test('buy rejects insufficient blood and payment', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->shop = [
        ['id' => 'BIA', 'name' => 'San Biagio', 'description' => '', 'cost' => 3, 'expiry' => 3],
    ];
    // Captured card with blood value below cost, no blood reserve.
    $state->players->p1->captured = ['1B']; // value 1+11→11, suit B adds 0 → wait, getCardBloodValue: 1→11 base, B adds 0 → 11
    // Use a tiny card to force insufficient
    $state->players->p1->captured = ['2B']; // 2+0=2; cost=3; need 1 more, have 0 blood
    $state->players->p1->blood = 0;
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('$BIA(2B)'))->toBeFalse()
        ->and($validator->getFirstError())->toContain('Insufficient blood');
});

test('buy accepts when payment + blood cover cost', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->shop = [
        ['id' => 'BIA', 'name' => 'San Biagio', 'description' => '', 'cost' => 3, 'expiry' => 3],
    ];
    $state->players->p1->captured = ['7D']; // 7 + 3 (D) = 10, well over cost 3
    $state->players->p1->blood = 0;
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('$BIA(7D)'))->toBeTrue();
});

test('use rejects santo not owned', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->players->p1->santi = [];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('@BIA'))->toBeFalse()
        ->and($validator->getFirstError())->toContain('do not own');
});

test('use accepts when player owns santo', function () {
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->players->p1->santi = ['BIA'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('@BIA'))->toBeTrue();
});

test('capture honors mutations via getEffectiveCard', function () {
    // Mutate 3C → 7C. Played 7S should be able to capture mutated 3C.
    $state = (new ScopaEngine(VALIDATOR_SEED))->getState();
    $state->table = ['3C'];
    $state->mutations = ['3C' => '7C'];
    $state->players->p1->hand = ['7S'];
    $state->currentTurnPlayer = 'p1';

    $validator = MoveValidator::fromState($state, 'p1');
    expect($validator->validate('7Sx3C#'))->toBeTrue();
});
