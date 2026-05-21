<?php

namespace App\Http\Controllers;

use App\Enums\GameStateEnum;
use App\Events\GameFinished;
use App\Events\GameStateUpdated;
use App\Events\RoundFinished;
use App\GameEngine\ScopaEngine;
use App\GameEngine\Validators\MoveValidator;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\GameActionRequest;
use App\Models\Game;
use App\Models\GameEvent;
use GuzzleHttp\Promise\Create;
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
            'player_1_id' => auth()->id(),
            'player_2_id' => null,
            'seed' => Str::random(16),
            'status' => GameStateEnum::WAITING_FOR_PLAYERS,
        ]);

        return response()->json([
            'status' => 'created',
            'game_id' => $game->id,
            'seed' => $game->seed,
        ], 201);
    }

    public function join(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        if ($game->player_2_id !== null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Partita già al completo',
            ], 422);
        }

        $game->player_2_id = auth()->id();
        $game->status = GameStateEnum::PLAYING;
        $game->save();

        return response()->json([
            'status' => 'joined',
            'game_id' => $game->id,
            'seed' => $game->seed,
        ], 200);
    }

    /**
     * SHOW: Recupera lo stato attuale (Replay PGN)
     */
    public function show($gameId, Request $request)
    {
        $game = Game::findOrFail($gameId);

        try {
            $playerIndex = $this->getLoggedPlayerIndex($game);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Not your game.'], 403);
        }

        $events = GameEvent::where('game_id', $gameId)->orderBy('sequence_number')->get();

        $engine = new ScopaEngine($game->seed);
        $state = $engine->replay($events->all());

        return response()->json([
            'gameStatus' => $game->status,
            'state' => $state->toPublicView($playerIndex),
        ]);
    }

    /**
     * Gestisce una singola micro-azione di gioco (Live)
     */
    public function handleAction(Request $request, $gameId)
    {
        // TODO ADD VALIDATION FROM GAMEACTIONREQUEST
        $requestData = $request->all();


        //try {
            return DB::transaction(function () use ($gameId, $requestData) {
                return $this->_handleAction($gameId, $requestData['action']);
            });
        /*} catch (\Exception $e) {
            Log::error('Errore gioco: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }*/
    }

    private function _handleAction($gameId, $action)
    {
        // 1. Lock della riga del gioco per evitare race conditions sulla sequenza
        $game = Game::where('id', $gameId)->lockForUpdate()->firstOrFail();

        // 2. Recupero storia eventi
        $events = GameEvent::where('game_id', $gameId)
            ->orderBy('sequence_number')
            ->get();

        // 3. Ricostruzione stato tramite Engine
        $engine = new ScopaEngine($game->seed,

            // On round ended callback
            function ($results) use ($game) {
                broadcast(new RoundFinished($results, $game->id));
                sleep(5);
            },

            // On game ended callback
            function ($results) use ($game) {
                broadcast(new GameFinished($results, $game->id));
            }
        );

        $engine->replay($events->all());

        $playerIndex = $this->getLoggedPlayerIndex($game);

        error_log(sprintf("DBG auth=%s p1=%s p2=%s pi=%s turn=%s action=%s",
            var_export(auth()->id(), true),
            var_export($game->player_1_id, true),
            var_export($game->player_2_id, true),
            var_export($playerIndex, true),
            $engine->getState()->currentTurnPlayer,
            $action
        ));

        // 4. Validazione mossa (regole Scopa) prima dell'applicazione
        $validator = MoveValidator::fromState($engine->getState(), $playerIndex);
        if (!$validator->validate($action)) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->getFirstError(),
                'errors' => $validator->getErrors(),
            ], 422);
        }

        // 5. Esecuzione mossa
        $engine->applyAction($playerIndex, $action);


        // Se la partita è finita, aggiorna lo stato del gioco e non inviare aggiornamenti WebSocket
        if ($engine->getState()->isGameOver) {
            $scores = $engine->getState()->scores->toArray();
            $winnerPid = $engine->getState()->scores->getWinner();
            $winnerUserId = match ($winnerPid) {
                'p1' => $game->player_1_id,
                'p2' => $game->player_2_id,
                default => null,
            };

            $game->status = GameStateEnum::FINISHED;
            $game->winner_id = $winnerUserId;
            $game->final_score_p1 = $scores['p1'];
            $game->final_score_p2 = $scores['p2'];
            $game->save();
        } else {
            // Invio WebSocket del nuovo stato
            broadcast(new GameStateUpdated($game->id, $engine->getState()->toPublicView('p1'), $game->player_1_id));
            broadcast(new GameStateUpdated($game->id, $engine->getState()->toPublicView('p2'), $game->player_2_id));
        }

        // 5. Persistenza dell'evento
        GameEvent::create([
            'game_id' => $gameId,
            'sequence_number' => $events->count() + 1,
            'actor_id' => $playerIndex,
            'pgn_action' => $action,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Azione processata',
        ]);
    }


}
