<?php

namespace App\Http\Controllers;

use App\Services\KpiSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KpiController extends Controller
{
    public function __invoke(Request $request, KpiSummary $summary): Response
    {
        $filters = $request->validate([
            'period' => ['nullable', 'string', 'in:today,month,all'],
        ]);
        $period = $filters['period'] ?? 'month';

        return Inertia::render('Kpi/Index', [
            'filters' => ['period' => $period],
            ...$summary->summarize($period),
        ]);
    }
}
