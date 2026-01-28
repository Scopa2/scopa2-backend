<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasUuids;
    protected $fillable = [
        'id',
        'player_1_id',
        'player_2_id',
        'seed',
        'status',
    ];
}
