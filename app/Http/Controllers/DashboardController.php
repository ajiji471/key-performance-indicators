<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Platform;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'sort'     => ['nullable', 'in:asc,desc'],
        ]);

        $agents = Agent::query()
            ->with(['accounts.platform'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['platform'] ?? null, function ($query, $platformId) {
                $query->whereHas('accounts', fn ($q) => $q->where('platform_id', $platformId));
            })
            ->orderBy('name', $filters['sort'] ?? 'asc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Agent $agent) => [
                'id'          => $agent->id,
                'name'        => $agent->name,
                'avatar_url'  => $agent->avatarUrl(),
                'device_user' => $agent->device_user,
                'created_at'  => $agent->created_at?->toDateString(),
                'platforms'   => $agent->accounts->map(fn ($account) => [
                    'slug'       => $account->platform->slug,
                    'name'       => $account->platform->name,
                    'identifier' => $account->identifier,
                    'status'     => $account->status?->value ?? 'registered',
                ])->values(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                // Today = snapshot status akun, sama dengan tabel
                'today' => $this->countAccountsByPlatformAndStatus(),
                'month' => $this->countByPlatformAndStatus(today()->startOfMonth(), today()->endOfMonth()),
                'all'   => $this->countByPlatformAndStatus(),
            ],
            'agents'    => $agents,
            'platforms' => Platform::orderBy('sort')->get(['id', 'slug', 'name']),
            'filters'   => $request->only(['search', 'platform', 'sort']),
        ]);
    }

    // Hitung jumlah agent per platform per status dari task (month/all)
    private function countByPlatformAndStatus(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $stats = Task::query()
            ->when($from, fn ($q) => $q->whereBetween('task_date', [$from->toDateString(), $to->toDateString()]))
            ->selectRaw('platform_id, status, COUNT(DISTINCT agent_id) as total')
            ->groupBy('platform_id', 'status')
            ->get()
            ->groupBy('platform_id');

        return $this->platformStatusTotals($stats);
    }

    // Hitung jumlah agent per platform per status dari akun (today)
    private function countAccountsByPlatformAndStatus(): array
    {
        $stats = AgentAccount::query()
            ->selectRaw('platform_id, status, COUNT(DISTINCT agent_id) as total')
            ->groupBy('platform_id', 'status')
            ->get()
            ->groupBy('platform_id');

        return $this->platformStatusTotals($stats);
    }

    private function platformStatusTotals(Collection $stats): array
    {
        return Platform::orderBy('sort')->get()->map(function ($platform) use ($stats) {
            $platformStats = $stats[$platform->id] ?? collect();

            $totals = ['name' => $platform->name];
            foreach (AccountStatus::cases() as $status) {
                $totals[$status->value] = (int) ($platformStats->where('status', $status->value)->first()->total ?? 0);
            }

            return $totals;
        })->values()->toArray();
    }
}
