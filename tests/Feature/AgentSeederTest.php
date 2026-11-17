<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\User;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentSeederTest extends TestCase
{
    use RefreshDatabase;

    // --- Integration: file seed nyata (136 agent CSV) ---

    public function test_seeder_memetakan_136_agent_csv_ke_skema_project(): void
    {
        $this->buatInputer();
        $this->seed(AgentSeeder::class);

        $this->assertDatabaseCount('agents', 136);
        $this->assertDatabaseHas('users', ['email' => config('seed.admin.email')]);
        $this->assertDatabaseEmpty('account_stage_credits');

        $agent = Agent::with('accounts.platform')
            ->where('name', 'AS1-001 - Made Neelson')
            ->firstOrFail();

        $this->assertSame('madeneelson@gmail.com', $agent->email);
        $this->assertNull($agent->recovery_email);
        $this->assertSame('exiting', $agent->context);
        $this->assertSame('WastuKencana1990', $agent->password);
        $this->assertSame('2026-06-11', $agent->created_at->toDateString());
        $this->assertNull($agent->birth_date);

        $statuses = $agent->accounts->mapWithKeys(
            fn ($account) => [$account->platform->slug => $account->status->value]
        );

        $this->assertSame('logged_in', $statuses['google']);
        $this->assertSame('logged_in', $statuses['youtube']);
        $this->assertSame('logged_in', $statuses['facebook']);
        $this->assertSame('logged_in', $statuses['x']);
        $this->assertSame('logged_in', $statuses['pinterest']);
        $this->assertArrayNotHasKey('instagram', $statuses);
        $this->assertArrayNotHasKey('tiktok', $statuses);
    }

    public function test_recovery_email_dari_kolom_eksplisit_bukan_multi_google(): void
    {
        $this->buatInputer();
        $this->seed(AgentSeeder::class);

        $isma = Agent::where('name', 'AS1-001 Isma Hamizah')->firstOrFail();
        $this->assertSame('ismahamizah@gmail.com', $isma->email);
        $this->assertSame('wiwinwinara014@gmail.com', $isma->recovery_email);
    }

    public function test_created_by_resolve_ajiji_gasti_dan_fallback_ajiji(): void
    {
        [$ajiji, $gasti] = $this->buatInputer();
        $this->seed(AgentSeeder::class);

        $this->assertSame(96, Agent::where('created_by', $ajiji->id)->count());
        $this->assertSame(40, Agent::where('created_by', $gasti->id)->count());

        // creator CSV 'nurhuda' tak ada user dev → fallback Ajiji
        $made = Agent::where('name', 'AS1-001 - Made Neelson')->firstOrFail();
        $this->assertSame($ajiji->id, $made->created_by);
    }

    public function test_birth_dan_created_at_dari_kolom_khusus(): void
    {
        $this->buatInputer();
        $this->seed(AgentSeeder::class);

        $wilson = Agent::where('name', 'AS1-001 - Wilson Alung 2')->firstOrFail();
        $this->assertSame('1991-05-21', $wilson->birth_date->toDateString());
        $this->assertSame('2026-11-06', $wilson->created_at->toDateString());
        $this->assertSame('device-41', $wilson->context);
        $this->assertSame('aloengwillsoun@gmail.com', $wilson->email);
    }

    public function test_koordinat_dipulihkan_ke_bbox_indonesia(): void
    {
        $this->buatInputer();
        $this->seed(AgentSeeder::class);

        $withCoords = Agent::whereNotNull('latitude')->whereNotNull('longitude')->get();
        $this->assertCount(60, $withCoords);

        foreach ($withCoords as $agent) {
            $lat = (float) $agent->latitude;
            $long = (float) $agent->longitude;
            $this->assertGreaterThanOrEqual(-11, $lat);
            $this->assertLessThanOrEqual(6, $lat);
            $this->assertGreaterThanOrEqual(1, abs($lat));
            $this->assertGreaterThanOrEqual(95, $long);
            $this->assertLessThanOrEqual(141, $long);
        }
    }

    public function test_address_terisi_17_agent(): void
    {
        $this->buatInputer();
        $this->seed(AgentSeeder::class);

        $this->assertSame(17, Agent::whereNotNull('address')->count());
    }

    public function test_seeder_aman_dijalankan_ulang(): void
    {
        $this->buatInputer();
        $this->seed(AgentSeeder::class);
        $this->assertDatabaseEmpty('account_stage_credits');

        $agents = Agent::count();
        $accounts = AgentAccount::count();

        $this->seed(AgentSeeder::class);

        $this->assertSame($agents, Agent::count());
        $this->assertSame($accounts, AgentAccount::count());
        $this->assertDatabaseCount('agents', 136);
        $this->assertDatabaseEmpty('account_stage_credits');
    }

    // --- Synthetic fixture: edge case parsing (data terkontrol, non-PII) ---

    public function test_parse_birth_semua_format(): void
    {
        $this->seedSynthetic([
            $this->row('B1', ['birth' => '21-5-1991']),
            $this->row('B2', ['birth' => ':23-08-1999']),
            $this->row('B3', ['birth' => '24 aug 2001']),
            $this->row('B4', ['birth' => '14 Mar 99']),
            $this->row('B5', ['birth' => '22 Juli 1995']),
            $this->row('B6', ['birth' => '5 Nov 2001']),
            $this->row('B7', ['birth' => 'bukan tanggal']),
            $this->row('B8', ['birth' => '']),
        ]);

        $expect = [
            'B1' => '1991-05-21', 'B2' => '1999-08-23', 'B3' => '2001-08-24',
            'B4' => '1999-03-14', 'B5' => '1995-07-22', 'B6' => '2001-11-05',
            'B7' => null, 'B8' => null,
        ];

        foreach ($expect as $name => $date) {
            $agent = Agent::where('name', $name)->firstOrFail();
            $this->assertSame($date, $agent->birth_date?->toDateString(), "birth {$name}");
        }
    }

    public function test_parse_coordinate_recovery_format_rusak(): void
    {
        $this->seedSynthetic([
            $this->row('C1', ['latitude' => '-61.234.567', 'longitude' => '1.067.890.123']),
            $this->row('C2', ['latitude' => '-6.200.093', 'longitude' => '1.103.346.633']),
            $this->row('C3', ['latitude' => '-6.2000000', 'longitude' => '106.7890123']),
            $this->row('C4', ['latitude' => '', 'longitude' => '']),
        ]);

        $c1 = Agent::where('name', 'C1')->firstOrFail();
        $this->assertSame('-6.1234567', $c1->latitude);
        $this->assertSame('106.7890123', $c1->longitude);

        $c2 = Agent::where('name', 'C2')->firstOrFail();
        $this->assertSame('-6.2000930', $c2->latitude);
        $this->assertSame('110.3346633', $c2->longitude);

        $c3 = Agent::where('name', 'C3')->firstOrFail();
        $this->assertSame('-6.2000000', $c3->latitude);
        $this->assertSame('106.7890123', $c3->longitude);

        $c4 = Agent::where('name', 'C4')->firstOrFail();
        $this->assertNull($c4->latitude);
        $this->assertNull($c4->longitude);
    }

    public function test_resolve_creator_ke_user_dev(): void
    {
        [$ajiji, $gasti] = $this->buatInputer();

        $this->seedSynthetic([
            $this->row('U1', ['creator' => 'ajiji']),
            $this->row('U2', ['creator' => 'gasti']),
            $this->row('U3', ['creator' => 'nurhuda']),
            $this->row('U4', ['creator' => '']),
        ]);

        $this->assertSame($ajiji->id, Agent::where('name', 'U1')->firstOrFail()->created_by);
        $this->assertSame($gasti->id, Agent::where('name', 'U2')->firstOrFail()->created_by);
        $this->assertSame($ajiji->id, Agent::where('name', 'U3')->firstOrFail()->created_by);
        $this->assertSame($ajiji->id, Agent::where('name', 'U4')->firstOrFail()->created_by);
    }

    public function test_recovery_email_eksplisit_dan_google_extra_didrop(): void
    {
        $this->buatInputer();

        $this->seedSynthetic([
            $this->row('R1', [
                'google_id' => 'a@gmail.com | b@gmail.com | c@gmail.com',
                'google_status' => 'Logged In',
                'email_recovery' => 'rec@gmail.com',
            ]),
            $this->row('R2', [
                'google_id' => 'solo@gmail.com',
                'google_status' => 'Logged In',
                'email_recovery' => '',
            ]),
        ]);

        $r1 = Agent::with('accounts.platform')->where('name', 'R1')->firstOrFail();
        $this->assertSame('a@gmail.com', $r1->email);
        $this->assertSame('rec@gmail.com', $r1->recovery_email);
        $google = $r1->accounts->firstWhere('platform.slug', 'google');
        $this->assertSame('a@gmail.com', $google->identifier);

        $r2 = Agent::where('name', 'R2')->firstOrFail();
        $this->assertSame('solo@gmail.com', $r2->email);
        $this->assertNull($r2->recovery_email);
    }

    public function test_device_ke_context_dan_address_terpetakan(): void
    {
        $this->seedSynthetic([
            $this->row('D1', ['device' => '(device farm-03)', 'address' => 'Jl. Contoh No. 1']),
        ]);

        $d1 = Agent::where('name', 'D1')->firstOrFail();
        $this->assertSame('(device farm-03)', $d1->context);
        $this->assertSame('Jl. Contoh No. 1', $d1->address);
        $this->assertNull($d1->device_user);
        $this->assertNull($d1->device_number);
    }

    // --- Helpers ---

    /**
     * @return array{0: User, 1: User}
     */
    private function buatInputer(): array
    {
        $ajiji = User::factory()->create([
            'name' => config('seed.inputer1.name'),
            'email' => config('seed.inputer1.email'),
            'role' => Role::User,
        ]);
        $gasti = User::factory()->create([
            'name' => config('seed.inputer2.name'),
            'email' => config('seed.inputer2.email'),
            'role' => Role::User,
        ]);

        return [$ajiji, $gasti];
    }

    private function row(string $name, array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'password' => 'pw',
            'email_recovery' => '',
            'device' => '',
            'google_id' => '', 'google_status' => '',
            'youtube_id' => '', 'youtube_status' => '',
            'facebook_id' => '', 'facebook_status' => '',
            'instagram_id' => '', 'instagram_status' => '',
            'x_id' => '', 'x_status' => '',
            'tiktok_id' => '', 'tiktok_status' => '',
            'pinterest_id' => '', 'pinterest_status' => '',
            'creator' => 'ajiji',
            'create_at' => '',
            'birth' => '',
            'latitude' => '',
            'longitude' => '',
            'address' => '',
        ], $overrides);
    }

    private function seedSynthetic(array $rows): void
    {
        $seeder = new class($rows) extends AgentSeeder
        {
            public function __construct(private array $data) {}

            protected function rows(): array
            {
                return $this->data;
            }
        };

        $this->app->instance(get_class($seeder), $seeder);
        $this->seed(get_class($seeder));
    }
}
