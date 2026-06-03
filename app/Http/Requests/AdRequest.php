<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title'    => ['nullable', 'string', 'max:120'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
        // image required only on create
        if ($this->isMethod('post')) {
            $rules['image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        } else {
            $rules['image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        }
        return $rules;
    }
}
