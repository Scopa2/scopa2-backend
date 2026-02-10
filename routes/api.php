<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

// Crea una nuova partita (ritorna l'UUID del game)
Route::post('/games', [GameController::class, 'create']);

// Partecipa ad una partita esistente come secondo giocatore
Route::post('/games/{gameId}/join', [GameController::class, 'join']);

// Recupera lo stato (per riconnessione o caricamento iniziale)
Route::get('/games/{gameId}', [GameController::class, 'show']);

// Gestisce le azioni (compra, usa, gioca carta)
Route::post('/games/{gameId}/action', [GameController::class, 'handleAction']);

// Gestisce le azioni del bot
Route::post('/games/{gameId}/bot-play', [GameController::class, 'botPlay']);

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'API route not found',
    ], 404);
});
