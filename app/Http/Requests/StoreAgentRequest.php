<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role dijaga middleware role:admin,user
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'avatar'                 => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email'                  => ['required', 'string', 'email', 'max:255'],
            'recovery_email'         => ['nullable', 'string', 'email', 'max:255'],
            'password'               => ['required', 'string', 'min:6', 'max:255'],
            'birth_date'             => ['nullable', 'date'],
            'address'                => ['nullable', 'string'],
            'latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'job'                    => ['nullable', 'string', 'max:255'],
            'hobby'                  => ['nullable', 'string', 'max:255'],
            'context'                => ['nullable', 'string'],
            'device_user'            => ['nullable', 'string', 'max:255'],
            'device_number'          => ['nullable', 'string', 'max:30'],
            'device_released_at'     => ['nullable', 'date'],

            'accounts'               => ['nullable', 'array', 'max:7'],
            'accounts.*.platform_id' => ['required', 'integer', Rule::exists('platforms', 'id'), 'distinct'],
            'accounts.*.identifier'  => ['nullable', 'string', 'max:255'],
            'accounts.*.password'    => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.mimes'                    => 'Avatar harus jpg, jpeg, png, atau webp.',
            'avatar.max'                      => 'Ukuran avatar maksimal 2 MB.',
            'accounts.*.platform_id.distinct' => 'Platform tidak boleh dobel.',
            'accounts.*.platform_id.exists'   => 'Platform tidak dikenal.',
        ];
    }
}