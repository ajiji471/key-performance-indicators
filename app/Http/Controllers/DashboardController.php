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
            'sort'     => ['nullable', 'in:asc,desc'],
        ]);

        $agents = Agent::query()
            ->with(['accounts.platform', 'tasks'])
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
                'platforms'   => $agent->accounts->map(function ($account) use ($agent) {
                    $latestTask = $agent->tasks->firstWhere('platform_id', $account->platform_id);
                    return [
                        'slug'       => $account->platform->slug,
                        'name'       => $account->platform->name,
                        'identifier' => $account->identifier,
                        'status'     => $latestTask ? $latestTask->status->value : 'registered',
                    ];
                })->values(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'today' => $this->countByPlatformAndStatus(today(), today()),
                'month' => $this->countByPlatformAndStatus(today()->startOfMonth(), today()->endOfMonth()),
                'all'   => $this->countByPlatformAndStatus(),
            ],
            'agents'    => $agents,
            'platforms' => Platform::orderBy('sort')->get(['id', 'slug', 'name']),
            'filters'   => $request->only(['search', 'platform', 'sort']),
        ]);
    }

    // Hitung jumlah agent per platform per status
    private function countByPlatformAndStatus(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $platforms = Platform::orderBy('sort')->get();
        
        $query = Task::query()
            ->when($from, fn ($q) => $q->whereBetween('task_date', [$from->toDateString(), $to->toDateString()]));
        
        // Group by platform dan status
        $stats = $query
            ->selectRaw('platform_id, status, COUNT(DISTINCT agent_id) as total')
            ->groupBy('platform_id', 'status')
            ->get()
            ->groupBy('platform_id');
        
        return $platforms->map(function ($platform) use ($stats) {
            $platformStats = $stats[$platform->id] ?? collect([]);
            return [
                'name'       => $platform->name,
                'logged_in'  => (int) ($platformStats->where('status', 'logged_in')->first()->total ?? 0),
                'registered' => (int) ($platformStats->where('status', 'registered')->first()->total ?? 0),
                'trouble'    => (int) ($platformStats->where('status', 'trouble')->first()->total ?? 0),
            ];
        })->values()->toArray();
    }
}