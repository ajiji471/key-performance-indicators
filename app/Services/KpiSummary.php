<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class KpiSummary
{
    public function summarize(string $period): array
    {
        $timezone = config('app.timezone');
        $now = CarbonImmutable::now($timezone);
        [$from, $until] = match ($period) {
            'today' => [$now->startOfDay(), $now->startOfDay()->addDay()],
            'month' => [$now->startOfMonth(), $now->startOfMonth()->addMonth()],
            'all' => [null, null],
        };

        $totals = DB::table('account_stage_credits')
            ->whereIn('platform_slug', ['google', 'facebook', 'youtube', 'instagram', 'x', 'tiktok', 'pinterest'])
            ->whereIn('stage', ['registered', 'logged_in'])
            ->when($from, fn ($query) => $query->where('achieved_at', '>=', $from))
            ->when($until, fn ($query) => $query->where('achieved_at', '<', $until))
            ->select('inputer_id')
            ->selectRaw('COUNT(DISTINCT DATE(achieved_at)) AS active_days')
            ->selectRaw("SUM(CASE WHEN stage = 'registered' THEN 1 ELSE 0 END) AS registered_credits")
            ->selectRaw("SUM(CASE WHEN stage = 'logged_in' THEN 1 ELSE 0 END) AS login_credits")
            ->selectRaw("SUM(CASE WHEN stage = 'registered' AND platform_slug = 'google' THEN 1 ELSE 0 END) AS email_registered")
            ->selectRaw("SUM(CASE WHEN stage = 'logged_in' AND platform_slug = 'google' THEN 1 ELSE 0 END) AS email_login")
            ->groupBy('inputer_id')
            ->get()
            ->keyBy('inputer_id');

        $users = User::query()->get(['id', 'name'])->keyBy('id');
        $inputers = $users->keys()->merge($totals->keys())->unique()->map(function ($id) use ($users, $totals) {
            $user = $users->get($id);
            $total = $totals->get($id);
            $days = (int) ($total->active_days ?? 0);
            $registered = (int) ($total->registered_credits ?? 0);
            $login = (int) ($total->login_credits ?? 0);
            $emailRegistered = (int) ($total->email_registered ?? 0);
            $emailLogin = (int) ($total->email_login ?? 0);

            return [
                'inputer_id' => (int) $id,
                'name' => $user?->name ?? 'Inputer tidak tersedia',
                'is_deleted' => $user === null,
                'active_days' => $days,
                'email_registered' => [
                    'total' => $emailRegistered,
                    'average' => $days > 0 ? $emailRegistered / $days : 0,
                    'category' => match (true) {
                        $days === 0 || $emailRegistered < $days * 3 => 'below_target',
                        $emailRegistered <= $days * 4 => 'within_target',
                        default => 'above_target',
                    },
                ],
                'email_login' => [
                    'total' => $emailLogin,
                    'average' => $days > 0 ? $emailLogin / $days : 0,
                ],
                'agent_registered' => [
                    'credits' => $registered,
                    'total' => $registered / 7,
                    'average' => $days > 0 ? $registered / ($days * 7) : 0,
                    'category' => match (true) {
                        $days === 0 => 'below_baseline',
                        $registered * 100 >= $days * 7 * 133 => 'excellent',
                        $registered * 100 >= $days * 7 * 125 => 'better',
                        $registered >= $days * 7 => 'baseline',
                        default => 'below_baseline',
                    },
                ],
                'agent_login' => [
                    'credits' => $login,
                    'total' => $login / 7,
                    'average' => $days > 0 ? $login / ($days * 7) : 0,
                ],
            ];
        })->sortBy([['name', 'asc'], ['inputer_id', 'asc']])->values()->all();

        return [
            'period' => [
                'timezone' => $timezone,
                'from' => $from?->toIso8601String(),
                'until' => $until?->toIso8601String(),
            ],
            'kpi' => [
                'has_activity' => $totals->isNotEmpty(),
                'inputers' => $inputers,
            ],
        ];
    }
}
