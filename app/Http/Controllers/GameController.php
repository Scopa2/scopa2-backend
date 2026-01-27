<?php

namespace App\Http\Controllers;

use App\Events\GameStateUpdated;
use App\Http\Requests\GameActionRequest;
use App\Models\Game;
use App\Models\GameEvent;
use App\GameEngine\ScopaEngine;
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
            'id' => (string) Str::uuid(),
            'player_1_id' => $request->user()?->id ?? 'p1',
            'player_2_id' => $request->input('opponent_id', 'bot'),
            'seed' => Str::random(16), // Il cuore del sistema deterministico
            'status' => 'playing',
        ]);

        return response()->json([
            'status' => 'created',
            'game_id' => $game->id,
            'seed' => $game->seed
        ], 201);
    }

    /**
     * SHOW: Recupera lo stato attuale (Replay PGN)
     */
    public function show($gameId, Request $request)
    {
        $game = Game::findOrFail($gameId);
        $userId = $request->user()?->id ?? 'p1';

        $events = GameEvent::where('game_id', $gameId)->orderBy('sequence_number')->get();

        $engine = new ScopaEngine($game->seed);
        $state = $engine->replay($events->all());

        return response()->json([
            'game_status' => $game->status,
            'state' => $state->toPublicView($userId)
        ]);
    }

    /**
     * Gestisce una singola micro-azione di gioco (Live)
     */
    public function handleAction(GameActionRequest $request, $gameId)
    {
        $validated = $request->validated();
        $userId = $request->user()?->id ?? 'p1';

        try {
            return DB::transaction(function () use ($gameId, $userId, $validated) {

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
                // Se la mossa è impossibile (es. non hai la carta), l'engine lancia Exception
                $engine->applyAction($userId, $validated['action']);

                // 5. Persistenza dell'evento
                GameEvent::create([
                    'game_id' => $gameId,
                    'sequence_number' => $events->count() + 1,
                    'actor_id' => $userId,
                    'pgn_action' => $validated['action']
                ]);

                // 6. Generazione stato aggiornato per il Broadcast
                $updatedState = $engine->getState();

                // Invio WebSocket (Broadcast a tutti i client connessi al canale della partita)
                broadcast(new GameStateUpdated($gameId, $updatedState))->toOthers();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Azione processata',
                    // Ritorniamo lo stato privato al mittente per sincronizzazione immediata
                    'state' => $updatedState->toPublicView($userId)
                ]);
            });

        } catch (\Exception $e) {
            Log::error("Errore gioco: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422); // Unprocessable Entity
        }
    }
}
