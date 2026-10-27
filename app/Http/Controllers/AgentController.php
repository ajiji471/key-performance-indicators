<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use App\Models\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort'   => ['nullable', 'in:asc,desc'],
        ]);

        $agents = Agent::query()
            ->withCount('accounts')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name', $filters['sort'] ?? 'asc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Agent $agent) => [
                'id'             => $agent->id,
                'name'           => $agent->name,
                'email'          => $agent->email,
                'device_user'    => $agent->device_user,
                'accounts_count' => $agent->accounts_count,
                'created_at'     => $agent->created_at?->toDateString(),
            ]);

        return Inertia::render('Agents/Index', [
            'agents'  => $agents,
            'filters' => $request->only(['search', 'sort']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Agents/Create');
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        Agent::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return Redirect::route('dashboard')->with('success', 'Agent ditambahkan.');
    }

    public function show(Agent $agent): Response
    {
        $agent->load(['accounts.platform', 'creator:id,name']);

        return Inertia::render('Agents/Show', [
            'agent' => [
                'id'                 => $agent->id,
                'name'               => $agent->name,
                'email'              => $agent->email,
                'recovery_email'     => $agent->recovery_email,
                'birth_date'         => $agent->birth_date?->toDateString(),
                'address'            => $agent->address,
                'latitude'           => $agent->latitude,
                'longitude'          => $agent->longitude,
                'job'                => $agent->job,
                'hobby'              => $agent->hobby,
                'context'            => $agent->context,
                'device_user'        => $agent->device_user,
                'device_number'      => $agent->device_number,
                'device_released_at' => $agent->device_released_at?->toDateString(),
                'created_at'         => $agent->created_at?->toDateString(),
                'creator'            => $agent->creator?->name,
                'accounts'           => $agent->accounts->map(fn ($account) => [
                    'id'          => $account->id,
                    'platform_id' => $account->platform_id,
                    'slug'        => $account->platform->slug,
                    'platform'    => $account->platform->name,
                    'identifier'  => $account->identifier,
                ])->values(),
            ],
            'platforms' => Platform::orderBy('sort')->get(['id', 'slug', 'name']),
        ]);
    }

    public function edit(Agent $agent): Response
    {
        return Inertia::render('Agents/Edit', [
            'agent' => [
                'id'                 => $agent->id,
                'name'               => $agent->name,
                'email'              => $agent->email,
                'recovery_email'     => $agent->recovery_email,
                'birth_date'         => $agent->birth_date?->toDateString(),
                'address'            => $agent->address,
                'latitude'           => $agent->latitude,
                'longitude'          => $agent->longitude,
                'job'                => $agent->job,
                'hobby'              => $agent->hobby,
                'context'            => $agent->context,
                'device_user'        => $agent->device_user,
                'device_number'      => $agent->device_number,
                'device_released_at' => $agent->device_released_at?->toDateString(),
            ],
        ]);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): RedirectResponse
    {
        $data = $request->validated();

        // password kosong = jangan timpa password lama
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $agent->fill($data)->save();

        return Redirect::route('agents.show', $agent)->with('success', 'Agent diperbarui.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return Redirect::route('agents.index')->with('success', 'Agent dihapus.');
    }
}