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
            'id' => (string) Str::uuid(),
            'player_1_id' => $request->user()?->id ?? 'p1',
            'player_2_id' => $request->input('opponent_id', 'bot'),
            'seed' => Str::random(16), // Il cuore del sistema deterministico
            'status' => 'playing',
            'has_bot' => $request->input('has_bot', false),
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
        $userId = $request->query('player', 'p1');
        // $userId = $request->user()?->id ?? 'p1';

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
    public function handleAction(GameActionRequest $request, $gameId, $userId = null)
    {
        $validated = $request->validated();
        $userId = $userId ?? $request->query('player', 'p1');

        try {
            return DB::transaction(function () use ($gameId, $userId, $validated) {
                return $this->_handleAction($gameId, $userId, $validated['action']);
            });
        } catch (\Exception $e) {
            Log::error('Errore gioco: '.$e->getMessage());

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

        // Invio WebSocket
        broadcast(new GameStateUpdated($gameId, $updatedState))->toOthers();

        if ($game->has_bot && $userId === 'p1') {
            DB::afterCommit(function () use ($gameId) {
                $this->_botPlay($gameId, 'bot');
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Azione processata',
            'state' => $updatedState->toPublicView($userId),
        ]);
    }

    public function botPlay($gameId, $botId = 'bot')
    {
        try {
            DB::transaction(function () use ($gameId, $botId) {
                $this->_botPlay($gameId, $botId);
            });
        } catch (\Exception $e) {
            Log::error('Errore bot: '.$e->getMessage());
        }
    }

    private function _botPlay($gameId, $botId = 'bot')
    {
        $game = Game::findOrFail($gameId);
        if (! $game->has_bot) {
            return;
        }

        $events = GameEvent::where('game_id', $gameId)->orderBy('sequence_number')->get();
        $engine = new ScopaEngine($game->seed);
        $state = $engine->replay($events->all());

        if ($state->getCurrentPlayerId() !== $botId) {
            return;
        }

        $botHand = $state->getHand($botId);
        $tableCards = $state->getTable();

        if (empty($botHand)) {
            return;
        }

        // Logica del bot: gioca una carta a caso dalla mano
        $cardToPlay = $botHand[array_rand($botHand)];
        $actionPgn = "PLAY({$cardToPlay})";

        // Se ci sono carte sul tavolo, prova a prendere una carta a caso
        if (! empty($tableCards)) {
            $cardToTake = $tableCards[array_rand($tableCards)];
            $actionPgn = "TAKE({$cardToPlay}, [{$cardToTake}])";
        }

        $this->_handleAction($gameId, $botId, $actionPgn);
    }
}
