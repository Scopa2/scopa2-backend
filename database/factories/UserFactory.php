<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'elo' => 1000,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Set a custom ELO rating for the user.
     */
    public function withElo(int $elo): static
    {
        return $this->state(fn (array $attributes) => [
            'elo' => $elo,
        ]);
    }
}
