<?php

namespace App\Http\Controllers;

use App\Events\GameStateUpdated;
use App\GameEngine\ScopaEngine;
use App\Http\Requests\GameActionRequest;
use App\Models\Game;
use App\Models\GameEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GameController extends Controller
{

    /**
     * CREAZIONE: Inizia una nuova sessione di gioco
     */
    public function create(Request $request)
    {
        $game = Game::create([
            'id' => (string)Str::uuid(),
            'player_1_id' => 'p1',
            'player_2_id' => 'p2',
            'seed' => Str::random(16),
            'status' => 'playing',
            'has_bot' => true,
        ]);

        return response()->json([
            'status' => 'created',
            'game_id' => $game->id,
            'seed' => $game->seed,
        ], 201);
    }

    /**
     * SHOW: Recupera lo stato attuale (Replay PGN)
     */
    public function show($gameId, Request $request)
    {
        $game = Game::findOrFail($gameId);

        // get the query parameter "player"
        $userId = $request->query('player');

        $events = GameEvent::where('game_id', $gameId)->orderBy('sequence_number')->get();

        $engine = new ScopaEngine($game->seed);
        $state = $engine->replay($events->all());

        return response()->json([
            'game_status' => $game->status,
            'state' => $state->toPublicView($userId),
        ]);
    }

    /**
     * Gestisce una singola micro-azione di gioco (Live)
     */
    public function handleAction(GameActionRequest $request, $gameId)
    {
        $validated = $request->validated();
        $userId = $request->query('player');

        try {
            return DB::transaction(function () use ($gameId, $userId, $validated) {
                return $this->_handleAction($gameId, $userId, $validated['action']);
            });
        } catch (\Exception $e) {
            Log::error('Errore gioco: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function _handleAction($gameId, $userId, $action)
    {
        // 1. Lock della riga del gioco per evitare race conditions sulla sequenza
        $game = Game::where('id', $gameId)->lockForUpdate()->firstOrFail();

        // 2. Recupero storia eventi
        $events = GameEvent::where('game_id', $gameId)
            ->orderBy('sequence_number')
            ->get();

        // 3. Ricostruzione stato tramite Engine
        $engine = new ScopaEngine($game->seed);
        $engine->replay($events->all());

        // 4. Validazione logica ed esecuzione
        $engine->applyAction($userId, $action);

        // 5. Persistenza dell'evento
        GameEvent::create([
            'game_id' => $gameId,
            'sequence_number' => $events->count() + 1,
            'actor_id' => $userId,
            'pgn_action' => $action,
        ]);

        // 6. Generazione stato aggiornato per il Broadcast
        $updatedState = $engine->getState();

//        // 7. Se c'è un bot e tocca a lui, gioca
//        if ($game->has_bot && $updatedState->currentTurnPlayer === 'p2') {
//            // It's the bot's turn. Let it play.
//            $botAction = $engine->getBestBotAction();
//            $engine->applyAction('p2', $botAction);
//
//            // Persist the bot's action
//            GameEvent::create([
//                'game_id' => $gameId,
//                'sequence_number' => $events->count() + 2, // +1 for user, +1 for bot
//                'actor_id' => 'p2',
//                'pgn_action' => $botAction,
//            ]);
//
//            // Get the final state after bot's move
//            $updatedState = $engine->getState();
//        }

        $gameStateView = $updatedState->toPublicView($userId);

        // Invio WebSocket
        broadcast(new GameStateUpdated($gameStateView));

        return response()->json([
            'status' => 'success',
            'message' => 'Azione processata',
            'state' => $gameStateView
        ]);
    }


}
