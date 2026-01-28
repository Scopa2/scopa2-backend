<?php

namespace App\GameEngine\Validators;

use App\GameEngine\GameConstants;
use App\GameEngine\GameState;
use App\GameEngine\GameUtilities;
use App\GameEngine\ScopaEngine;
use App\GameEngine\ScopaNotationParser;
use App\Http\Requests\GameActionRequest;
use App\Models\Game;
use App\Models\GameEvent;
use Illuminate\Support\Collection;

class MoveValidator
{
    private ScopaEngine $engine;
    private GameState $state;
    private string $pid;
    private array $errors = [];

    public function __construct(string $gameId, string $pid)
    {
        $this->pid = $pid;

        $game = Game::findOrFail($gameId);
        $events = GameEvent::where('game_id', $gameId)
            ->orderBy('sequence_number')
            ->get();

        $this->engine = new ScopaEngine($game->seed);
        $this->engine->replay($events->all());
        $this->state = $this->engine->getState();
    }

    /**
     * Validate a move action
     */
    public function validate(string $action): bool
    {
        $this->errors = [];

        // Check if it's the player's turn
        if (!($this->state->currentTurnPlayer === $this->pid)) {
            $this->errors[] = "It's not your turn.";
            return false;
        }

        $action = ScopaNotationParser::parse($action);

        switch ($action['type']) {
            case GameConstants::TYPE_SHOP_BUY:
                //TODO: Implement buy validation logic
                break; // NON cambia turno

            case GameConstants::TYPE_MODIFIER_USE:
                //TODO: Implement modifier use validation logic
                break; // NON cambia turno

            case GameConstants::TYPE_CARD_PLAY:
                //set parsed values in the request
                return $this->validatePlayCard($action['card'], $action['targets']);
        }

        return true;
    }

    /**
     * Validate playing a card from hand
     */
    private function validatePlayCard(string $card, array $targets): bool
    {
        $state = $this->engine->getState();

        // Check if player has this card in hand
        if (!$state->playerHasCard($this->pid, $card)) {
            $this->errors[] = "You don't have the card {$card} in your hand.";
            return false;
        }

        // If capturing, validate the capture
        if (!empty($targets)) {
            return $this->validateCapture($card, $targets);
        }
        return true;
    }

    /**
     * Validate a capture action
     */
    private function validateCapture(string $playedCard, array $capturedCards): bool
    {
        $tableCards = $this->state->table;

        // Check if all captured cards are on the table
        foreach ($capturedCards as $capCardNotation) {
            if (!in_array($capCardNotation, $tableCards)) {
                $this->errors[] = "Captured card {$capCardNotation} is not on the table.";
                return false;
            }
        }

        // Validate sum: captured cards must sum to played card value
        $capturedSum = 0;
        foreach ($capturedCards as $capCardNotation) {
            $capturedSum += GameUtilities::getCardValue($capCardNotation);
        }

        if ($capturedSum !== ($playerCardValue = GameUtilities::getCardValue($playedCard))) {
            $this->errors[] = "Invalid capture: the sum of captured cards ({$capturedSum}) must equal the played card value ({$playerCardValue}).";
            return false;
        }

        return true;
    }


    /**
     * Validate a buy action
     */
    private function validateBuyAction(string $action): bool
    {
        // TODO: Implement buy validation logic
        return true;
    }

    /**
     * Validate a use action
     */
    private function validateUseAction(string $action): bool
    {
        // TODO: Implement use validation logic
        return true;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}

