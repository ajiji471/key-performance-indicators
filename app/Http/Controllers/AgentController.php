<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AgentController extends Controller
{
    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // baris platform tanpa identifier = tidak diisi, tidak perlu disimpan
        $accounts = collect($data['accounts'] ?? [])
            ->filter(fn (array $row) => filled($row['identifier'] ?? null))
            ->values()
            ->all();

        unset($data['accounts'], $data['avatar']);

        $avatarPath = $request->file('avatar')?->store('agents/avatars', 'public');

        try {
            DB::transaction(function () use ($data, $accounts, $avatarPath, $request) {
                $agent = Agent::create([
                    ...$data,
                    'avatar_path' => $avatarPath,
                    'created_by'  => $request->user()->id,
                ]);

                if ($accounts !== []) {
                    $agent->accounts()->createMany($accounts);
                }
            });
        } catch (Throwable $e) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }

            throw $e;
        }

        return Redirect::route('dashboard')->with('success', 'Agent ditambahkan.');
    }

    // dipakai popup profile; sengaja tanpa page Inertia
    public function show(Agent $agent): JsonResponse
    {
        $agent->load(['accounts.platform', 'creator:id,name']);

        return response()->json([
            'agent' => [
                'id'                 => $agent->id,
                'name'               => $agent->name,
                'avatar_url'         => $agent->avatarUrl(),
                'email'              => $agent->email,
                'recovery_email'     => $agent->recovery_email,
                'password'           => $agent->password,
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
                    'password'    => $account->password,
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): RedirectResponse
    {
        $data = $request->validated();

        // password kosong = jangan timpa password lama; akun platform punya endpoint sendiri
        unset($data['accounts'], $data['avatar']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $agent->fill($data)->save();

        return Redirect::route('dashboard')->with('success', 'Agent diperbarui.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return Redirect::route('dashboard')->with('success', 'Agent dihapus.');
    }
}