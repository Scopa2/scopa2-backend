<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameEvent extends Model
{
    protected $fillable = [
        'game_id',
        'sequence_number',
        'actor_id',
        'pgn_action',
    ];
}
