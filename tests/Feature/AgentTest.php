<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    private function login(Role $role = Role::Admin): static
    {
        return $this->actingAs(User::factory()->create(['role' => $role]));
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name'          => 'Budi',
            'email'         => 'budi@example.com',
            'device_user'   => 'budi-device',
            'device_number' => '081234567890',
            'password'      => 'rahasia123',
            ...$overrides,
        ];
    }

    private function accountsPayload(int $count = 2): array
    {
        return Platform::factory()->count($count)->create()
            ->map(fn (Platform $platform) => [
                'platform_id' => $platform->id,
                'identifier'  => "akun-{$platform->id}@example.com",
                'password'    => 'akun-rahasia',
            ])
            ->all();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/agents', $this->payload())->assertRedirect('/login');
    }

    public function test_semua_role_bisa_lihat_daftar_agent(): void
    {
        Agent::factory()->create(['name' => 'Budi']);

        foreach (Role::cases() as $role) {
            $this->login($role)
                ->get('/dashboard')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Dashboard')
                    ->has('agents.data', 1)
                );
        }
    }

    public function test_search_dan_sort_agent(): void
    {
        Agent::factory()->create(['name' => 'Budi']);
        Agent::factory()->create(['name' => 'Andi']);

        $this->login()
            ->get('/dashboard?sort=desc')
            ->assertInertia(fn (Assert $page) => $page->where('agents.data.0.name', 'Budi'));

        $this->login()
            ->get('/dashboard?search=And')
            ->assertInertia(fn (Assert $page) => $page->has('agents.data', 1));
    }

    public function test_admin_dan_user_bisa_tambah_agent(): void
    {
        foreach ([Role::Admin, Role::User] as $role) {
            $this->login($role)->post('/agents', $this->payload())->assertRedirect();

            $agent = Agent::latest('id')->first();
            $this->assertSame('Budi', $agent->name);
            $this->assertSame('rahasia123', $agent->password);
            $this->assertNotNull($agent->created_by);
        }

        // password tersimpan terenkripsi, bukan plaintext
        $this->assertNotSame('rahasia123', DB::table('agents')->value('password'));
    }

    public function test_auditor_tidak_bisa_tambah_agent(): void
    {
        $this->login(Role::Auditor)->post('/agents', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_validasi_agent(): void
    {
        $this->login()->post('/agents', ['name' => '', 'email' => 'bukan-email', 'password' => '123'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_detail_agent_berisi_akun_platform(): void
    {
        $agent = Agent::factory()->create();
        $agent->accounts()->create(['platform_id' => Platform::factory()->create()->id, 'identifier' => 'budi@gmail.com']);

        $this->login(Role::Auditor)
            ->getJson("/agents/{$agent->id}")
            ->assertOk()
            ->assertJsonPath('agent.accounts.0.identifier', 'budi@gmail.com');
    }

    public function test_store_agent_lengkap_dengan_akun_platform(): void
    {
        $payload = [
            ...$this->payload(),
            'birth_date'         => '2000-01-02',
            'address'            => 'Jl. Contoh 1',
            'latitude'           => '-6.2000000',
            'longitude'          => '106.8166667',
            'job'                => 'Operator',
            'hobby'              => 'Futsal',
            'context'            => 'Agent baru',
            'device_released_at' => '2025-05-01',
            'accounts'           => $this->accountsPayload(2),
        ];

        $this->login()->post('/agents', $payload)->assertRedirect(route('dashboard'));

        $agent = Agent::latest('id')->firstOrFail();

        $this->assertSame('2000-01-02', $agent->birth_date->toDateString());
        $this->assertSame('Operator', $agent->job);
        $this->assertCount(2, $agent->accounts);
        $this->assertSame('akun-rahasia', $agent->accounts->first()->password);
        $this->assertNotSame('akun-rahasia', DB::table('agent_accounts')->value('password'));
    }

    public function test_store_agent_dengan_avatar(): void
    {
        Storage::fake('public');

        $this->login()->post('/agents', [
            ...$this->payload(),
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertRedirect(route('dashboard'));

        $agent = Agent::latest('id')->firstOrFail();

        $this->assertNotNull($agent->avatar_path);
        Storage::disk('public')->assertExists($agent->avatar_path);
    }

    public function test_platform_dobel_ditolak(): void
    {
        $platform = Platform::factory()->create();

        $this->login()->post('/agents', [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'a@example.com'],
                ['platform_id' => $platform->id, 'identifier' => 'b@example.com'],
            ],
        ])->assertSessionHasErrors('accounts.1.platform_id');

        $this->assertDatabaseCount('agents', 0);
        $this->assertDatabaseCount('agent_accounts', 0);
    }

    public function test_baris_akun_tanpa_identifier_diabaikan(): void
    {
        $platform = Platform::factory()->create();

        $this->login()->post('/agents', [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => '', 'password' => ''],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('agents', 1);
        $this->assertDatabaseCount('agent_accounts', 0);
    }

    public function test_update_tanpa_password_tidak_menimpa_password_lama(): void
    {
        $agent = Agent::factory()->create(['password' => 'password-lama']);

        $this->login()
            ->patch("/agents/{$agent->id}", ['name' => 'Budi Baru', 'email' => 'baru@example.com'])
            ->assertRedirect(route('dashboard'));

        $agent->refresh();

        $this->assertSame('Budi Baru', $agent->name);
        $this->assertSame('password-lama', $agent->password);
    }

    public function test_auditor_tidak_bisa_update_dan_hapus_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->login(Role::Auditor)->patch("/agents/{$agent->id}", $this->payload())->assertForbidden();
        $this->login(Role::Auditor)->delete("/agents/{$agent->id}")->assertForbidden();
    }

    public function test_hapus_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->login()
            ->delete("/agents/{$agent->id}")
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
    }

    public function test_password_agent_tidak_bocor_di_dashboard(): void
    {
        Agent::factory()->create(['password' => 'rahasia-banget-123']);

        $list = $this->login()->get('/dashboard')->getContent();

        $this->assertStringNotContainsString('rahasia-banget-123', $list);
    }
}