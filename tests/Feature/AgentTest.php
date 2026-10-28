<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Agent;
use App\Models\AgentAccount;
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

    public function test_guest_bisa_lihat_dashboard_tapi_tidak_bisa_ubah_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->get('/dashboard')->assertOk();

        $this->post('/agents', $this->payload())->assertRedirect('/login');
        $this->patch("/agents/{$agent->id}", $this->payload())->assertRedirect('/login');
        $this->delete("/agents/{$agent->id}")->assertRedirect('/login');
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

    public function test_update_agent_field_lengkap(): void
    {
        $agent = Agent::factory()->create();

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(['name' => 'Budi Update']),
            'job'       => 'Supervisor',
            'address'   => 'Jl. Baru',
            'latitude'  => '-7.1000000',
            'longitude' => '110.1000000',
        ])->assertRedirect(route('dashboard'));

        $agent->refresh();

        $this->assertSame('Budi Update', $agent->name);
        $this->assertSame('Supervisor', $agent->job);
        $this->assertSame('Jl. Baru', $agent->address);
        $this->assertSame('-7.1000000', $agent->latitude);
    }

    public function test_update_agent_sinkron_akun_platform(): void
    {
        [$p1, $p2, $p3] = Platform::factory()->count(3)->create();
        $agent = Agent::factory()->create();

        $agent->accounts()->create(['platform_id' => $p1->id, 'identifier' => 'lama-1']);
        $agent->accounts()->create(['platform_id' => $p2->id, 'identifier' => 'lama-2']);

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $p1->id, 'identifier' => 'baru-1', 'password' => 'pw-baru'],
                ['platform_id' => $p2->id, 'identifier' => ''],
                ['platform_id' => $p3->id, 'identifier' => 'baru-3', 'password' => 'pw-3'],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('agent_accounts', ['agent_id' => $agent->id, 'platform_id' => $p1->id, 'identifier' => 'baru-1']);
        $this->assertDatabaseMissing('agent_accounts', ['agent_id' => $agent->id, 'platform_id' => $p2->id]);
        $this->assertDatabaseHas('agent_accounts', ['agent_id' => $agent->id, 'platform_id' => $p3->id, 'identifier' => 'baru-3']);

        $this->assertSame('pw-baru', $agent->accounts()->where('platform_id', $p1->id)->first()->password);
    }

    public function test_update_agent_password_akun_kosong_tidak_menimpa(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create();
        $agent->accounts()->create(['platform_id' => $platform->id, 'identifier' => 'lama', 'password' => 'pw-lama']);

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'baru', 'password' => ''],
            ],
        ])->assertRedirect(route('dashboard'));

        $account = $agent->accounts()->where('platform_id', $platform->id)->first();

        $this->assertSame('baru', $account->identifier);
        $this->assertSame('pw-lama', $account->password);
    }

    public function test_update_agent_ganti_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('agents/avatars/lama.png', 'lama');

        $agent = Agent::factory()->create(['avatar_path' => 'agents/avatars/lama.png']);

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(),
            'avatar' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect(route('dashboard'));

        $agent->refresh();

        $this->assertNotSame('agents/avatars/lama.png', $agent->avatar_path);
        Storage::disk('public')->assertMissing('agents/avatars/lama.png');
        Storage::disk('public')->assertExists($agent->avatar_path);
    }

    public function test_update_agent_tanpa_avatar_menjaga_avatar_lama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('agents/avatars/lama.png', 'lama');

        $agent = Agent::factory()->create(['avatar_path' => 'agents/avatars/lama.png']);

        $this->login()->patch("/agents/{$agent->id}", $this->payload(['name' => 'Tanpa Avatar']))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('agents/avatars/lama.png', $agent->refresh()->avatar_path);
        Storage::disk('public')->assertExists('agents/avatars/lama.png');
    }

    public function test_update_platform_dobel_ditolak(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create(['name' => 'Tetap']);

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(['name' => 'Berubah']),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'a@example.com'],
                ['platform_id' => $platform->id, 'identifier' => 'b@example.com'],
            ],
        ])->assertSessionHasErrors('accounts.1.platform_id');

        $this->assertSame('Tetap', $agent->refresh()->name);
        $this->assertDatabaseCount('agent_accounts', 0);
    }

    public function test_hapus_agent_menghapus_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('agents/avatars/hapus.png', 'x');

        $agent = Agent::factory()->create(['avatar_path' => 'agents/avatars/hapus.png']);

        $this->login()->delete("/agents/{$agent->id}")->assertRedirect(route('dashboard'));

        Storage::disk('public')->assertMissing('agents/avatars/hapus.png');
    }

    public function test_akun_baru_default_status_registered(): void
    {
        $platform = Platform::factory()->create();

        $this->login()->post('/agents', [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'a@example.com'],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(AccountStatus::Registered, AgentAccount::firstOrFail()->status);
        $this->assertSame('registered', DB::table('agent_accounts')->value('status'));
    }

    public function test_store_akun_dengan_status(): void
    {
        $platform = Platform::factory()->create();

        $this->login()->post('/agents', [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'a@example.com', 'status' => 'trouble'],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(AccountStatus::Trouble, AgentAccount::firstOrFail()->status);
    }

    public function test_update_status_akun_platform(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create();
        $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'lama',
            'status'      => AccountStatus::Registered,
        ]);

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'lama', 'status' => 'trouble'],
            ],
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(AccountStatus::Trouble, $agent->accounts()->firstOrFail()->status);
    }

    public function test_update_tanpa_status_menjaga_status_lama(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create();
        $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'lama',
            'status'      => AccountStatus::Recovered,
        ]);

        $this->login()->patch("/agents/{$agent->id}", [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'baru'],
            ],
        ])->assertRedirect(route('dashboard'));

        $account = $agent->accounts()->firstOrFail();

        $this->assertSame('baru', $account->identifier);
        $this->assertSame(AccountStatus::Recovered, $account->status);
    }

    public function test_status_akun_tidak_valid_ditolak(): void
    {
        $platform = Platform::factory()->create();

        $this->login()->post('/agents', [
            ...$this->payload(),
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'a@example.com', 'status' => 'ngawur'],
            ],
        ])->assertSessionHasErrors('accounts.0.status');

        $this->assertDatabaseCount('agents', 0);
        $this->assertDatabaseCount('agent_accounts', 0);
    }

    public function test_show_menyertakan_status_akun(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create();
        $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'a@example.com',
            'status'      => AccountStatus::Trouble,
        ]);

        $this->login()->getJson("/agents/{$agent->id}")
            ->assertOk()
            ->assertJsonPath('agent.accounts.0.status', 'trouble');
    }
}