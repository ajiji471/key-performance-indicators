<?php

namespace App\Http\Requests;

use App\Models\Agent;
use App\Models\AgentAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentAccountRequest extends FormRequest
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
                Rule::unique('agent_accounts', 'platform_id')
                    ->where('agent_id', $this->agent()->id)
                    ->ignore($this->account()->id),
            ],
            'identifier'  => ['required', 'string', 'max:255'],
            'password'    => ['nullable', 'string', 'max:255'],
        ];
    }

    private function agent(): Agent
    {
        return $this->route('agent');
    }

    private function account(): AgentAccount
    {
        return $this->route('account');
    }
}