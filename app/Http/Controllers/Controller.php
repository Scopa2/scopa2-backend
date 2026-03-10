<?php

namespace App\Http\Controllers;

use App\Models\Game;

abstract class Controller
{
    public function getLoggedPlayerSecret(): string
    {
        $playerSecret = request()->header('player_secret');
        if (!$playerSecret) {
            throw new \Exception("Player secret not provided");
        }
        return $playerSecret;
    }

    /**
     * @throws \Exception
     */
    public function getLoggedPlayerIndex(Game $game): ?string
    {
        $playerSecret = $this->getLoggedPlayerSecret();
        $playerIndex = $playerSecret === $game->player_1_id ? 'p1' : ($playerSecret === $game->player_2_id ? 'p2' : null);
        if($playerIndex === null) {
            throw new \Exception("Player not part of the game");
        }
        return $playerIndex;
    }


    protected function okResponse($data = [], $message = '')
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function createdResponse($data = [], $message = '')
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], 201);
    }

    protected function notFoundResponse($message = 'Resource not found')
    {
        return response()->json([
            'message' => $message
        ], 404);
    }

    protected function badRequestResponse($message = 'Bad request')
    {
        return response()->json([
            'message' => $message
        ], 400);
    }

    /**
     * @param array $fields
     * Field should be like ['field_name' => ['error message 1', 'error message 2']]
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationFailedResponse(array $fields = [])
    {
        return response()->json([
            'errors' => $fields
        ], 422);
    }

    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return response()->json([
            'message' => $message
        ], 401);
    }

    protected function errorResponse($message = 'Internal server error')
    {
        return response()->json([
            'message' => $message
        ], 500);
    }

}
