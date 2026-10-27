<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateAgentRequest extends StoreAgentRequest
{
    /**
     * Password opsional: kalau kosong, password lama dipertahankan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
        ];
    }
}