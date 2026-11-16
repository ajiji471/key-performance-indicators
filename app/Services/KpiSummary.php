<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class KpiSummary
{
    public function summarize(string $period, ?int $inputerId = null, ?CarbonImmutable $at = null): array
    {
        $timezone = config('app.timezone');
        $now = ($at ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        [$from, $until] = match ($period) {
            'today' => [$now->startOfDay(), $now->startOfDay()->addDay()],
            'week' => [
                $now->startOfWeek(CarbonImmutable::MONDAY),
                $now->startOfWeek(CarbonImmutable::MONDAY)->addWeek(),
            ],
            'month' => [$now->startOfMonth(), $now->startOfMonth()->addMonth()],
            'all' => [null, null],
        };

        $totals = DB::table('account_stage_credits')
            ->whereIn('platform_slug', ['google', 'facebook', 'youtube', 'instagram', 'x', 'tiktok', 'pinterest'])
            ->whereIn('stage', ['registered', 'logged_in'])
            ->when($inputerId !== null, fn ($query) => $query->where('inputer_id', $inputerId))
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

        $people = User::query()
            ->when($inputerId !== null, fn ($query) => $query->whereKey($inputerId))
            ->get(['id', 'name', 'role'])
            ->keyBy('id');
        $users = $people->filter(fn ($person) => $person->role === Role::User);
        $nonInputers = $people->reject(fn ($person) => $person->role === Role::User)->keys();
        $totals = $totals->reject(fn ($row, $id) => $nonInputers->contains($id));
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
                'name' => $user?->name ?? 'Inputer unavailable',
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

    public function profileCards(?int $inputerId): array
    {
        $at = CarbonImmutable::now(config('app.timezone'));
        $labels = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'all' => 'Total'];
        $reports = [];
        $periods = [];
        $rows = [];

        foreach ($labels as $key => $label) {
            $reports[$key] = $this->summarize($key, $inputerId, $at);
            $periods[$key] = ['label' => $label, ...$reports[$key]['period']];
            unset($periods[$key]['timezone']);
            $rows[$key] = collect($reports[$key]['kpi']['inputers'])->keyBy('inputer_id');
        }

        $inputers = $rows['all']->map(function (array $inputer) use ($rows, $labels) {
            $counts = [];
            foreach ($labels as $key => $label) {
                $row = $rows[$key]->get($inputer['inputer_id']);
                $counts[$key] = [
                    'email_registered' => $row['email_registered']['total'] ?? 0,
                    'email_login' => $row['email_login']['total'] ?? 0,
                    'agent_registered' => $row['agent_registered']['total'] ?? 0,
                    'agent_login' => $row['agent_login']['total'] ?? 0,
                    'registered_credits' => $row['agent_registered']['credits'] ?? 0,
                    'login_credits' => $row['agent_login']['credits'] ?? 0,
                ];
            }

            return [
                'inputer_id' => $inputer['inputer_id'],
                'name' => $inputer['name'],
                'is_deleted' => $inputer['is_deleted'],
                'periods' => $counts,
            ];
        })->values()->all();

        return ['kpi' => [
            'scope' => $inputerId === null ? 'all' : 'self',
            'timezone' => config('app.timezone'),
            'has_activity' => $reports['all']['kpi']['has_activity'],
            'periods' => $periods,
            'inputers' => $inputers,
        ]];
    }
}
