<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Http\Requests\StoreAgentAccountRequest;
use App\Http\Requests\UpdateAgentAccountRequest;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Services\AccountStageCreditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class AgentAccountController extends Controller
{
    public function __construct(private AccountStageCreditRecorder $credits) {}

    public function store(StoreAgentAccountRequest $request, Agent $agent): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['status'] ?? null)) {
            $data['status'] = AccountStatus::Registered;
        }

        DB::transaction(function () use ($request, $agent, $data) {
            $agent = Agent::query()->lockForUpdate()->findOrFail($agent->id);

            if ($agent->accounts()->where('platform_id', $data['platform_id'])->exists()) {
                throw ValidationException::withMessages(['platform_id' => 'The platform id has already been taken.']);
            }

            $account = $agent->accounts()->create($data);
            $this->credits->record($account, $request->user(), null);
        });

        return Redirect::route('dashboard')->with('success', 'Platform account added.');
    }

    public function update(UpdateAgentAccountRequest $request, Agent $agent, AgentAccount $account): RedirectResponse
    {
        $this->ensureBelongsToAgent($agent, $account);

        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (blank($data['status'] ?? null)) {
            unset($data['status']);
        }

        DB::transaction(function () use ($request, $agent, $account, $data) {
            $agent = Agent::query()->lockForUpdate()->findOrFail($agent->id);
            $account = $agent->accounts()->findOrFail($account->id);

            if ($agent->accounts()->where('platform_id', $data['platform_id'])->whereKeyNot($account->id)->exists()) {
                throw ValidationException::withMessages(['platform_id' => 'The platform id has already been taken.']);
            }

            $previousStatus = $account->status;
            $account->fill($data)->save();
            $this->credits->record($account, $request->user(), $previousStatus);
        });

        return Redirect::route('dashboard')->with('success', 'Platform account updated.');
    }

    public function destroy(Agent $agent, AgentAccount $account): RedirectResponse
    {
        $this->ensureBelongsToAgent($agent, $account);

        DB::transaction(function () use ($agent, $account) {
            $agent = Agent::query()->lockForUpdate()->findOrFail($agent->id);
            $agent->accounts()->findOrFail($account->id)->delete();
        });

        return Redirect::route('dashboard')->with('success', 'Platform account deleted.');
    }

    private function ensureBelongsToAgent(Agent $agent, AgentAccount $account): void
    {
        abort_unless($account->agent_id === $agent->id, 404);
    }
}