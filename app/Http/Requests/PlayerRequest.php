<?php

namespace App\Http\Requests;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'      => ['required', 'string', 'max:120'],
            'email'          => ['nullable', 'email', 'max:160'],
            'phone'          => ['nullable', 'string', 'max:40'],
            'paid_amount'    => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'payment_status' => ['nullable', Rule::in([
                Player::STATUS_UNPAID,
                Player::STATUS_PARTIAL,
                Player::STATUS_PAID,
            ])],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
