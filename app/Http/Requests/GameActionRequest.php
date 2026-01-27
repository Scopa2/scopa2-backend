<?php

namespace App\Http\Requests;

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
        return [
            // L'azione deve essere una stringa PGN valida (Regex di base)
            'action' => [
                'required',
                'string',
                'min:2',
                // Regex che valida i 3 formati: $Compra, @Usa o GiocataCarta
                'regex:/^(\$[A-Z0-9]+\(.*\)|@[A-Z0-9]+(\[.*\])?|[1-9][0-1]?[DCSB](x\(?.*\)?#?)?)$/'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.regex' => 'Il formato della mossa PGN non è valido.',
            'action.required' => 'Devi inviare un\'azione.',
        ];
    }
}
