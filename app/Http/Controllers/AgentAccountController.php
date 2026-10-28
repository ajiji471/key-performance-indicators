<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentAccountRequest;
use App\Http\Requests\UpdateAgentAccountRequest;
use App\Models\Agent;
use App\Models\AgentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class AgentAccountController extends Controller
{
    public function store(StoreAgentAccountRequest $request, Agent $agent): RedirectResponse
    {
        // create lewat relasi: agent_id diisi otomatis (tidak ada di #[Fillable])
        $agent->accounts()->create($request->validated());

        return Redirect::route('dashboard')->with('success', 'Platform account added.');
    }

    public function update(UpdateAgentAccountRequest $request, Agent $agent, AgentAccount $account): RedirectResponse
    {
        $this->ensureBelongsToAgent($agent, $account);

        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $account->fill($data)->save();

        return Redirect::route('dashboard')->with('success', 'Platform account updated.');
    }

    public function destroy(Agent $agent, AgentAccount $account): RedirectResponse
    {
        $this->ensureBelongsToAgent($agent, $account);

        $account->delete();

        return Redirect::route('dashboard')->with('success', 'Platform account deleted.');
    }

    private function ensureBelongsToAgent(Agent $agent, AgentAccount $account): void
    {
        abort_unless($account->agent_id === $agent->id, 404);
    }
}