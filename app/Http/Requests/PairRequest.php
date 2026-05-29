<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_a_id' => ['required', 'integer', 'exists:players,id'],
            'player_b_id' => ['required', 'integer', 'exists:players,id', 'different:player_a_id'],
            'label'       => ['nullable', 'string', 'max:80'],
        ];
    }
}
