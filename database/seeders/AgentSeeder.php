<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    private const PLATFORMS = ['google', 'youtube', 'facebook', 'instagram', 'x', 'tiktok', 'pinterest'];

    private const STATUSES = [
        'logged in'  => 'logged_in',
        'registered' => 'registered',
        'trouble'    => 'trouble',
        'fixing'     => 'trouble',
    ];

    public function run(): void
    {
        $this->call(PlatformSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'       => 'Admin',
                'password'   => Hash::make('password'),
                'role'       => Role::Admin,
                'is_active'  => true,
            ],
        );

        /** @var Collection<string, int> $platforms */
        $platforms = Platform::whereIn('slug', self::PLATFORMS)->pluck('id', 'slug');
        $rows = require database_path('seeders/data/agents.php');

        DB::transaction(function () use ($rows, $platforms, $admin) {
            foreach ($rows as $row) {
                $this->seedAgent($row, $platforms, $admin);
            }
        });
    }

    /**
     * @param  Collection<string, int>  $platforms
     */
    private function seedAgent(array $row, Collection $platforms, User $admin): void
    {
        $googleIds = $this->identifiers($row['google_id'] ?? null);
        $email = $googleIds[0] ?? Str::slug($row['name']).'@agent.local';
        $recoveryEmail = count($googleIds) > 1 ? implode(' | ', array_slice($googleIds, 1)) : null;

        $agent = Agent::updateOrCreate(
            ['name' => trim($row['name'])],
            [
                'email'          => $email,
                'recovery_email' => $recoveryEmail,
                'password'       => $row['password'],
                'context'        => $row['remark'],
                'birth_date'     => $this->birthDate($row['remark']),
                'created_by'     => $admin->id,
            ],
        );

        foreach (self::PLATFORMS as $slug) {
            $status = strtolower(trim((string) ($row["{$slug}_status"] ?? '')));

            if ($status === 'none') {
                continue;
            }

            $ids = $this->identifiers($row["{$slug}_id"] ?? null);

            if ($ids === []) {
                continue;
            }

            $agent->accounts()->updateOrCreate(
                ['platform_id' => $platforms[$slug]],
                [
                    'identifier' => $slug === 'google' ? $ids[0] : implode(' | ', $ids),
                    'password'   => $row['password'],
                    'status'     => self::STATUSES[$status] ?? 'registered',
                ],
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function identifiers(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return collect(explode('|', $value))
            ->map(fn (string $part) => preg_replace(
                '/^(new|ig|fb|tiktok|x|google|youtube|instagram|pinterest)\s*:\s*/i',
                '',
                trim($part),
            ))
            ->filter(fn (?string $part) => filled($part))
            ->unique()
            ->values()
            ->all();
    }

    private function birthDate(?string $remark): ?string
    {
        if (! $remark || ! preg_match('/birth\s*:\s*(\d{1,2})-(\d{1,2})-(\d{4})/i', $remark, $m)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
}
