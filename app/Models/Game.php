<?php

namespace App\Models;

use App\Enums\GameStateEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'player_1_id',
        'player_2_id',
        'seed',
        'status',
        'has_bot',
    ];

    protected $casts = [
        "has_bot" => 'boolean',
        "status" => GameStateEnum::class,
    ];

    public function events(): HasMany|Game
    {
        return $this->hasMany(GameEvent::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_2_id');
    }
}
