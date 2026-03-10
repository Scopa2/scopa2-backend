<?php

namespace App\Rules;

use App\GameEngine\Validators\MoveValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidGameMove implements ValidationRule
{
    private string $gameId;
    private string $playerId;

    public function __construct(string $gameId, string $playerId)
    {
        $this->gameId = $gameId;
        $this->playerId = $playerId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $validator = new MoveValidator($this->gameId, $this->playerId);

            if (!$validator->validate($value)) {
                $fail($validator->getFirstError() ?? 'Invalid move.');
            }
        } catch (\Exception $e) {
            $fail('Could not validate move: ' . $e->getMessage());
        }
    }
}

