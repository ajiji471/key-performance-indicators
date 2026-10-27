<?php

namespace App\Http\Requests;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentAccountRequest extends FormRequest
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
            'platform_id' => [
                'required',
                Rule::exists('platforms', 'id'),
                // satu agent hanya boleh punya satu akun per platform
                Rule::unique('agent_accounts', 'platform_id')->where('agent_id', $this->agent()->id),
            ],
            'identifier'  => ['required', 'string', 'max:255'],
            'password'    => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function agent(): Agent
    {
        return $this->route('agent');
    }
}