<?php

use App\Models\Game;
use App\Models\User;
use App\Enums\GameStateEnum;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a user with a Sanctum token and return [$user, $authHeaders].
 */
function actingAsUser(?array $attributes = []): array
{
    $user = User::factory()->create($attributes);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, ['Authorization' => "Bearer {$token}"]];
}

/**
 * Create a Game with two players using a fixed seed for reproducibility.
 */
function createGame(User $p1, ?User $p2 = null, string $seed = 'test_seed_fixed'): Game
{
    return Game::create([
        'id'          => (string) Str::uuid(),
        'player_1_id' => $p1->id,
        'player_2_id' => $p2?->id,
        'seed'        => $seed,
        'status'      => GameStateEnum::PLAYING,
    ]);
}
