<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

// Crea una nuova partita (ritorna l'UUID del game)
Route::post('/games', [GameController::class, 'create']);

// Recupera lo stato (per riconnessione o caricamento iniziale)
Route::get('/games/{gameId}', [GameController::class, 'show']);

// Gestisce le azioni (compra, usa, gioca carta)
Route::post('/games/{gameId}/action', [GameController::class, 'handleAction']);
