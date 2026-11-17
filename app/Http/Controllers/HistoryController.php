<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $from = $filters['from'] ?? null;
        $until = $filters['until'] ?? null;

        $logs = ActivityLog::query()
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($until, fn ($query) => $query->where('occurred_at', '<=', Carbon::parse($until)->endOfDay()))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('History/Index', [
            'logs' => $logs,
            'filters' => ['from' => $from, 'until' => $until],
            'timezone' => config('app.timezone'),
        ]);
    }
}
