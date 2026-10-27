<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'string', 'email', 'max:255'],
            'recovery_email'     => ['nullable', 'string', 'email', 'max:255'],
            'password'           => ['required', 'string', 'min:6', 'max:255'],
            'birth_date'         => ['nullable', 'date'],
            'address'            => ['nullable', 'string'],
            'latitude'           => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'          => ['nullable', 'numeric', 'between:-180,180'],
            'job'                => ['nullable', 'string', 'max:255'],
            'hobby'              => ['nullable', 'string', 'max:255'],
            'context'            => ['nullable', 'string'],
            'device_user'        => ['nullable', 'string', 'max:255'],
            'device_number'      => ['nullable', 'string', 'max:30'],
            'device_released_at' => ['nullable', 'date'],
        ];
    }
}