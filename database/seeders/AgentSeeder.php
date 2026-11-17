<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    private const PLATFORMS = ['google', 'youtube', 'facebook', 'instagram', 'x', 'tiktok', 'pinterest'];

    private const STATUSES = [
        'logged in' => 'logged_in',
        'registered' => 'registered',
        'trouble' => 'trouble',
        'fixing' => 'trouble',
    ];

    private const ID_MONTHS = [
        'januari' => 'january', 'februari' => 'february', 'maret' => 'march',
        'april' => 'april', 'mei' => 'may', 'juni' => 'june', 'juli' => 'july',
        'agustus' => 'august', 'september' => 'september', 'oktober' => 'october',
        'november' => 'november', 'desember' => 'december',
    ];

    public function run(): void
    {
        $this->call(PlatformSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => config('seed.admin.email')],
            [
                'name' => 'Admin',
                'password' => Hash::make(config('seed.admin.password')),
                'role' => Role::Admin,
                'is_active' => true,
            ],
        );

        /** @var Collection<string, int> $platforms */
        $platforms = Platform::whereIn('slug', self::PLATFORMS)->pluck('id', 'slug');
        $rows = $this->rows();

        DB::transaction(function () use ($rows, $platforms, $admin) {
            foreach ($rows as $row) {
                $this->seedAgent($row, $platforms, $admin);
            }
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function rows(): array
    {
        return require database_path('seeders/data/agents.php');
    }

    /**
     * @param  Collection<string, int>  $platforms
     */
    private function seedAgent(array $row, Collection $platforms, User $admin): void
    {
        $googleIds = $this->identifiers($row['google_id'] ?? null);
        $email = $googleIds[0] ?? Str::slug($row['name']).'@agent.local';
        $recoveryEmail = trim((string) ($row['email_recovery'] ?? '')) ?: null;

        $agent = Agent::updateOrCreate(
            ['name' => trim($row['name'])],
            [
                'email' => $email,
                'recovery_email' => $recoveryEmail,
                'password' => $row['password'],
                'context' => $row['device'] ?? null,
                'birth_date' => $this->parseBirth($row['birth'] ?? null),
                'address' => trim((string) ($row['address'] ?? '')) ?: null,
                'latitude' => $this->parseCoordinate($row['latitude'] ?? null, 'lat'),
                'longitude' => $this->parseCoordinate($row['longitude'] ?? null, 'long'),
                'created_by' => $this->resolveCreator($row['creator'] ?? null, $admin),
            ],
        );

        $createdAt = $this->parseCreatedAt($row['create_at'] ?? null);
        if ($createdAt !== null) {
            $agent->created_at = $createdAt;
            $agent->save();
        }

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
                    'password' => $row['password'],
                    'status' => self::STATUSES[$status] ?? 'registered',
                ],
            );
        }
    }

    private function resolveCreator(?string $creator, User $admin): int
    {
        $alias = strtolower(trim((string) $creator));
        $key = $alias === 'gasti' ? 'inputer2' : 'inputer1';
        $user = User::where('email', config("seed.{$key}.email"))->first();

        return $user?->id ?? $admin->id;
    }

    private function parseBirth(?string $value): ?string
    {
        $clean = ltrim(trim((string) $value), ': ');

        if ($clean === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $clean, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1])
                : null;
        }

        $normalized = str_ireplace(array_keys(self::ID_MONTHS), array_values(self::ID_MONTHS), $clean);

        try {
            return Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseCoordinate(?string $value, string $kind): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        [$min, $max] = $kind === 'lat' ? [-11.0, 6.0] : [95.0, 141.0];
        $acceptable = fn (float $val): bool => $val >= $min && $val <= $max && ($kind !== 'lat' || abs($val) >= 1);

        if (is_numeric($value) && $acceptable((float) $value)) {
            return round((float) $value, 7);
        }

        $negative = str_starts_with($value, '-');
        $digits = preg_replace('/[^0-9]/', '', $value);

        if ($digits === '') {
            return null;
        }

        $d = (int) $digits;

        for ($k = 3; $k <= 9; $k++) {
            $val = $d / (10 ** $k) * ($negative ? -1 : 1);
            if ($acceptable($val)) {
                return round($val, 7);
            }
        }

        return null;
    }

    private function parseCreatedAt(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = Carbon::createFromFormat('d/m/Y', $value);

        if ($date === false || $date->format('d/m/Y') !== $value) {
            return null;
        }

        return $date->startOfDay();
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
}
