<?php

namespace App\Http\Requests;

use App\Rules\ValidGameMove;
use Illuminate\Foundation\Http\FormRequest;

class GameActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Qui potresti verificare se l'utente appartiene effettivamente a questa partita
        return true;
    }

    public function rules(): array
    {
        $gameId = $this->route('gameId');
        $playerId = $this->query('player');

        return [
            // L'azione deve essere una stringa PGN valida (Regex di base)
            'action' => [
                'required',
                'string',
                'min:2',
                // Regex che valida i 3 formati: $Compra, @Usa o GiocataCarta
                'regex:/^(\$[A-Z0-9]+\(.*\)|@[A-Z0-9]+(\[.*\])?|[1-9][0-1]?[DCSB](x\(?.*\)?#?)?)$/',
                // Game state validation
                new ValidGameMove($gameId, $playerId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.regex' => 'Il formato della mossa PGN non è valido.',
            'action.required' => 'Devi inviare un\'azione.',
            'action.string' => 'L\'azione deve essere una stringa.',
            'action.min' => 'L\'azione deve essere lunga almeno :min caratteri.',
        ];
    }
}
