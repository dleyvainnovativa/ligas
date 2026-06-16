<?php

namespace App\Http\Requests;

use App\Models\League;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeagueRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route is already auth-gated; ownership is enforced in the controller via route model binding scoping.
        return true;
    }

    public function rules(): array
    {
        $leagueId = $this->route('league')?->id;

        return [
            'name'             => ['required', 'string', 'max:120'],
            'slug'             => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('leagues', 'slug')->ignore($leagueId),
            ],
            'description'      => ['nullable', 'string', 'max:2000'],
            'banner'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'format'           => ['required', Rule::in([League::FORMAT_INDIVIDUAL, League::FORMAT_PAIRS])],
            'num_jornadas'     => ['required', 'integer', 'min:1', 'max:52'],
            'cost'             => ['required', 'numeric', 'min:0', 'max:999999'],
            'days_of_week'     => ['required', 'array', 'min:1'],
            'days_of_week.*'   => [Rule::in(League::DAYS)],
            'time_slots'       => ['required', 'array', 'min:1'],
            'time_slots.*'     => ['regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'penalty_suplente' => ['required', 'integer', 'min:0', 'max:100'],
            'penalty_no_show'  => ['required', 'integer', 'min:0', 'max:100'],
            'jornadas_pares'   => ['nullable', 'integer', 'min:1', 'max:10'],
            'jornadas_nones'   => ['nullable', 'integer', 'min:1', 'max:10'],
            'status'           => ['nullable', Rule::in([
                League::STATUS_DRAFT,
                League::STATUS_ACTIVE,
                League::STATUS_COMPLETED,
                League::STATUS_ARCHIVED,
            ])],
            'points_win'  => ['nullable', 'integer', 'min:0', 'max:10'],
            'points_draw' => ['nullable', 'integer', 'min:0', 'max:10'],
            'points_loss' => ['nullable', 'integer', 'min:0', 'max:10'],
            'whatsapp_url' => ['nullable', 'url', 'max:300', function ($attr, $value, $fail) {
                if ($value && !\Illuminate\Support\Str::contains($value, ['whatsapp.com', 'wa.me'])) {
                    $fail('El enlace debe ser de WhatsApp (chat.whatsapp.com o wa.me).');
                }
            }],
            'promotion_relegation' => ['required', 'integer', 'min:1', 'max:4'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->slug)]);
        } elseif ($this->filled('name') && !$this->route('league')) {
            // Auto-slug on create when not provided.
            $this->merge(['slug' => Str::slug($this->name)]);
        }
    }

    public function messages(): array
    {
        return [
            'slug.regex'        => 'El slug solo puede contener minúsculas, números y guiones.',
            'time_slots.*.regex' => 'Cada horario debe tener formato HH:MM (24h).',
        ];
    }
}
