<?php

namespace App\GameEngine\Santi;

use App\GameEngine\GameState;

abstract class Santo
{
    public static ?string $id = null;
    public static ?string $name = null;
    public static ?string $description = null;
    public static ?int $cost = null;

    public abstract static function apply(string $pid, GameState $state, array $params = []): void;

    public static function getId() : string
    {
        if (static::$id === null) {
            throw new \Exception("Santo ID not set");
        }
        return static::$id;
    }
    public static function getName() : string
    {
        return static::$name ?? "Unnamed Santo";
    }
    public static function getDescription() : string
    {
        return static::$description ?? "No description";
    }
    public static function getCost() : int
    {
        return static::$cost ?? 0;
    }

    public static function serialize($expiry = null): array {
        return [
            'id' => static::getId(),
            'name' => static::getName(),
            'description' => static::getDescription(),
            'cost' => static::getCost(),
            'expiry' => $expiry
        ];
    }
}
