<?php

namespace App\GameEngine\Validators;

use App\GameEngine\GameConstants;
use App\GameEngine\GameState;
use App\GameEngine\GameUtilities;
use App\GameEngine\ScopaEngine;
use App\GameEngine\ScopaNotationParser;
use App\Models\Game;
use App\Models\GameEvent;

class MoveValidator
{
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

        $engine = new ScopaEngine($game->seed);
        $engine->replay($events->all());
        $this->state = $engine->getState();
    }

    public static function fromState(GameState $state, string $pid): self
    {
        $instance = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $instance->state = $state;
        $instance->pid = $pid;
        $instance->errors = [];
        return $instance;
    }

    public function validate(string $action): bool
    {
        $this->errors = [];

        if ($this->state->currentTurnPlayer !== $this->pid) {
            $this->errors[] = "It's not your turn.";
            return false;
        }

        $parsed = ScopaNotationParser::parse($action);

        switch ($parsed['type']) {
            case GameConstants::TYPE_SHOP_BUY:
                return $this->validateBuyAction($parsed['santo_id'], $parsed['payment'] ?? []);

            case GameConstants::TYPE_SANTO_USE:
                return $this->validateUseAction($parsed['santo_id'], $parsed['params'] ?? []);

            case GameConstants::TYPE_CARD_PLAY:
                return $this->validatePlayCard(
                    $parsed['card'],
                    $parsed['targets'],
                    $parsed['is_scopa'] ?? false
                );
        }

        $this->errors[] = "Unknown action type.";
        return false;
    }

    private function validatePlayCard(string $card, array $targets, bool $isScopa): bool
    {
        $player = $this->state->players->get($this->pid);

        if (!$player->hasCardInHand($card)) {
            $this->errors[] = "You don't have the card {$card} in your hand.";
            return false;
        }

        if (empty($targets)) {
            return true;
        }

        return $this->validateCapture($card, $targets);
    }

    private function validateCapture(string $playedCard, array $capturedCards): bool
    {
        $playedValue = GameUtilities::getCardValue($this->state->getEffectiveCard($playedCard));

        // Each captured card must be currently on the table.
        foreach ($capturedCards as $capCard) {
            if (!in_array($capCard, $this->state->table, true)) {
                $this->errors[] = "Captured card {$capCard} is not on the table.";
                return false;
            }
        }

        // Duplicates in targets list = trying to take the same card twice.
        if (count(array_unique($capturedCards)) !== count($capturedCards)) {
            $this->errors[] = "Duplicate capture target.";
            return false;
        }

        // Capture priority (regola classica Scopa): if any single table card matches played value exactly,
        // the player MUST capture that single card. Sum captures are forbidden when an exact match exists.
        $exactMatches = [];
        foreach ($this->state->table as $tc) {
            if (GameUtilities::getCardValue($this->state->getEffectiveCard($tc)) === $playedValue) {
                $exactMatches[] = $tc;
            }
        }

        if (!empty($exactMatches)) {
            if (count($capturedCards) !== 1) {
                $this->errors[] = "Exact match available: must capture a single card of value {$playedValue}.";
                return false;
            }
            $captured = $capturedCards[0];
            if (GameUtilities::getCardValue($this->state->getEffectiveCard($captured)) !== $playedValue) {
                $this->errors[] = "Must capture an exact-value card (value {$playedValue}).";
                return false;
            }
            return true;
        }

        // Sum capture: sum of captured values must equal played value.
        $sum = 0;
        foreach ($capturedCards as $capCard) {
            $sum += GameUtilities::getCardValue($this->state->getEffectiveCard($capCard));
        }
        if ($sum !== $playedValue) {
            $this->errors[] = "Invalid capture: captured sum ({$sum}) must equal played value ({$playedValue}).";
            return false;
        }

        return true;
    }

    private function validateBuyAction(string $santoId, array $payment): bool
    {
        if (!isset(GameConstants::SANTI[$santoId])) {
            $this->errors[] = "Unknown santo: {$santoId}.";
            return false;
        }

        $inShop = false;
        foreach ($this->state->shop as $shopSlot) {
            if (($shopSlot['id'] ?? null) === $santoId) {
                $inShop = true;
                break;
            }
        }
        if (!$inShop) {
            $this->errors[] = "Santo {$santoId} not available in shop.";
            return false;
        }

        $player = $this->state->players->get($this->pid);

        foreach ($payment as $card) {
            if (!$player->hasCardCaptured($card)) {
                $this->errors[] = "Payment card {$card} not in captured pile.";
                return false;
            }
        }

        if (count(array_unique($payment)) !== count($payment)) {
            $this->errors[] = "Duplicate payment card.";
            return false;
        }

        $santoClass = GameConstants::SANTI[$santoId];
        $cost = $santoClass::$cost ?? 0;

        $sacrificed = 0;
        foreach ($payment as $card) {
            $sacrificed += GameUtilities::getCardBloodValue($this->state->getEffectiveCard($card));
        }

        if ($sacrificed < $cost) {
            $missing = $cost - $sacrificed;
            if ($player->blood < $missing) {
                $this->errors[] = "Insufficient blood: need {$missing} more, have {$player->blood}.";
                return false;
            }
        }

        return true;
    }

    private function validateUseAction(string $santoId, array $params): bool
    {
        if (!isset(GameConstants::SANTI[$santoId])) {
            $this->errors[] = "Unknown santo: {$santoId}.";
            return false;
        }

        $player = $this->state->players->get($this->pid);
        if (!in_array($santoId, $player->santi, true)) {
            $this->errors[] = "You do not own santo {$santoId}.";
            return false;
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
