<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgentUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private function login(Role $role = Role::Admin): User
    {
        $user = User::factory()->create(['role' => $role, 'name' => 'Inputer Uji']);
        $this->actingAs($user);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'device_user' => 'budi-device',
            'device_number' => '081234567890',
            ...$overrides,
        ];
    }

    private function makeAgent(): Agent
    {
        return Agent::factory()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'device_user' => 'budi-device',
            'device_number' => '081234567890',
            'password' => 'rahasia123',
        ]);
    }

    public function test_dashboard_membawa_creator_dan_updater(): void
    {
        $creator = User::factory()->create(['name' => 'Ajiji']);
        Agent::factory()->create(['created_by' => $creator->id]);

        $this->login();

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agents.data.0.creator', 'Ajiji')
                ->where('agents.data.0.updater', null)
            );
    }

    public function test_update_profil_mencatat_updater(): void
    {
        $agent = $this->makeAgent();
        $user = $this->login();

        $this->patch("/agents/{$agent->id}", $this->payload(['name' => 'Budi Baru']))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($user->id, $agent->fresh()->updated_by);
    }

    public function test_ubah_akun_saja_mencatat_updater(): void
    {
        $platform = Platform::factory()->create();
        $agent = $this->makeAgent();
        $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'lama',
            'status' => AccountStatus::Registered,
        ]);
        $user = $this->login();

        $this->patch("/agents/{$agent->id}", $this->payload([
            'accounts' => [
                ['platform_id' => $platform->id, 'identifier' => 'lama', 'status' => 'trouble'],
            ],
        ]))->assertRedirect(route('dashboard'));

        $this->assertSame($user->id, $agent->fresh()->updated_by);
    }

    public function test_update_tanpa_perubahan_tidak_mencatat_updater(): void
    {
        $agent = $this->makeAgent();
        $this->login();

        $this->patch("/agents/{$agent->id}", $this->payload())
            ->assertRedirect(route('dashboard'));

        $this->assertNull($agent->fresh()->updated_by);
    }

    public function test_store_akun_platform_mencatat_updater(): void
    {
        $platform = Platform::factory()->create();
        $agent = $this->makeAgent();
        $user = $this->login();

        $this->post("/agents/{$agent->id}/accounts", [
            'platform_id' => $platform->id,
            'identifier' => 'baru@example.com',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame($user->id, $agent->fresh()->updated_by);
    }

    public function test_update_akun_platform_mencatat_updater(): void
    {
        $platform = Platform::factory()->create();
        $agent = $this->makeAgent();
        $account = $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'lama',
            'status' => AccountStatus::Registered,
        ]);
        $user = $this->login();

        $this->patch("/agents/{$agent->id}/accounts/{$account->id}", [
            'platform_id' => $platform->id,
            'identifier' => 'lama',
            'status' => 'logged_in',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame($user->id, $agent->fresh()->updated_by);
    }

    public function test_update_akun_tanpa_perubahan_tidak_mencatat_updater(): void
    {
        $platform = Platform::factory()->create();
        $agent = $this->makeAgent();
        $account = $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'lama',
            'status' => AccountStatus::Registered,
        ]);
        $this->login();

        $this->patch("/agents/{$agent->id}/accounts/{$account->id}", [
            'platform_id' => $platform->id,
            'identifier' => 'lama',
            'status' => 'registered',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull($agent->fresh()->updated_by);
    }

    public function test_hapus_akun_platform_mencatat_updater(): void
    {
        $platform = Platform::factory()->create();
        $agent = $this->makeAgent();
        $account = $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier' => 'lama',
        ]);
        $user = $this->login();

        $this->delete("/agents/{$agent->id}/accounts/{$account->id}")
            ->assertRedirect(route('dashboard'));

        $this->assertSame($user->id, $agent->fresh()->updated_by);
    }

    public function test_show_membawa_nama_updater(): void
    {
        $agent = $this->makeAgent();
        $this->login();

        $this->patch("/agents/{$agent->id}", $this->payload(['name' => 'Budi Baru']));

        $this->getJson("/agents/{$agent->id}")
            ->assertOk()
            ->assertJsonPath('agent.updater', 'Inputer Uji');
    }
}
