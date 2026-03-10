<?php

namespace App\Enums;

enum GameStateEnum: string {
    case WAITING_FOR_PLAYERS = 'waiting_for_players';
    case PLAYING = 'playing';
    case FINISHED = 'finished';
}
