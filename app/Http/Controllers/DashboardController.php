<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Platform;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'numeric', 'exists:platforms,id'],
            'sort'     => ['nullable', 'in:asc,desc'],
        ]);

        $agents = Agent::query()
            ->with('accounts.platform')
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
                'device_user' => $agent->device_user,
                // Kirim array platforms dgn status tunggal per platform
                'platforms'   => $agent->accounts->map(function ($account) use ($agent) {
                    $latestTask = $agent->tasks()
                        ->where('platform_id', $account->platform_id)
                        ->latest('task_date')
                        ->first();
                    
                    return [
                        'slug'       => $account->platform->slug,
                        'name'       => $account->platform->name,
                        'identifier' => $account->identifier,
                        'status'     => $latestTask ? $latestTask->status->value : 'registered', // Default registered kalo blm ada task
                    ];
                })->values(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'today' => $this->countByStatus(today(), today()),
                'month' => $this->countByStatus(today()->startOfMonth(), today()->endOfMonth()),
                'all'   => $this->countByStatus(),
            ],
            'agents'    => $agents,
            'platforms' => Platform::orderBy('sort')->get(['id', 'slug', 'name']),
            'filters'   => $request->only(['search', 'platform', 'sort']),
        ]);
    }

    private function countByStatus(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $rows = Task::query()
            ->when($from, fn ($q) => $q->whereBetween('task_date', [$from->toDateString(), $to->toDateString()]))
            ->selectRaw('status, COUNT(DISTINCT agent_id) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'registered' => (int) ($rows['registered'] ?? 0),
            'logged_in'  => (int) ($rows['logged_in'] ?? 0),
            'trouble'    => (int) ($rows['trouble'] ?? 0),
        ];
    }
}