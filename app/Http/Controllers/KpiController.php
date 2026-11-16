<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\KpiSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KpiController extends Controller
{
    public function __invoke(Request $request, KpiSummary $summary): Response
    {
        $viewer = $request->user();
        $inputerId = match ($viewer->role) {
            Role::Admin, Role::Auditor => null,
            Role::User => (int) $viewer->id,
        };

        return Inertia::render('Kpi/Index', $summary->profileCards($inputerId));
    }
}
