<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\MatchmakingController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'games', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/', [GameController::class, 'create']);
    Route::post('/{gameId}/join', [GameController::class, 'join']);
    Route::get('/{gameId}', [GameController::class, 'show']);
    Route::post('/{gameId}/action', [GameController::class, 'handleAction']);
});

Route::group(['prefix' => 'matchmaking', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/join', [MatchmakingController::class, 'join']);
    Route::post('/leave', [MatchmakingController::class, 'leave']);
    Route::get('/status', [MatchmakingController::class, 'status']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
});

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'API route not found',
    ], 404);
});
