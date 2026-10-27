<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentAccountTest extends TestCase
{
    use RefreshDatabase;

    private function login(Role $role = Role::Admin): static
    {
        return $this->actingAs(User::factory()->create(['role' => $role]));
    }

    public function test_user_bisa_tambah_akun_platform(): void
    {
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();

        $this->login(Role::User)
            ->post("/agents/{$agent->id}/accounts", [
                'platform_id' => $platform->id,
                'identifier'  => 'budi@gmail.com',
                'password'    => 'rahasia123',
            ])
            ->assertRedirect(route('agents.show', $agent));

        $account = AgentAccount::first();

        $this->assertSame($agent->id, $account->agent_id);
        $this->assertSame('budi@gmail.com', $account->identifier);
        $this->assertSame('rahasia123', $account->password);
        $this->assertNotSame('rahasia123', DB::table('agent_accounts')->value('password'));
    }

    public function test_satu_agent_hanya_satu_akun_per_platform(): void
    {
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $agent->accounts()->create(['platform_id' => $platform->id, 'identifier' => 'budi@gmail.com']);

        $this->login()
            ->post("/agents/{$agent->id}/accounts", [
                'platform_id' => $platform->id,
                'identifier'  => 'lain@gmail.com',
            ])
            ->assertSessionHasErrors('platform_id');

        $this->assertDatabaseCount('agent_accounts', 1);
    }

    public function test_platform_harus_ada(): void
    {
        $agent = Agent::factory()->create();

        $this->login()
            ->post("/agents/{$agent->id}/accounts", ['platform_id' => 999, 'identifier' => 'budi@gmail.com'])
            ->assertSessionHasErrors('platform_id');
    }

    public function test_update_akun_tanpa_password_tidak_menimpa_password_lama(): void
    {
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'budi@gmail.com',
            'password'    => 'password-lama',
        ]);

        $this->login()
            ->patch("/agents/{$agent->id}/accounts/{$account->id}", [
                'platform_id' => $platform->id,
                'identifier'  => 'budi-baru@gmail.com',
            ])
            ->assertRedirect(route('agents.show', $agent));

        $account->refresh();

        $this->assertSame('budi-baru@gmail.com', $account->identifier);
        $this->assertSame('password-lama', $account->password);
    }

    public function test_hapus_akun_platform(): void
    {
        $agent = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $agent->accounts()->create(['platform_id' => $platform->id, 'identifier' => 'budi@gmail.com']);

        $this->login()
            ->delete("/agents/{$agent->id}/accounts/{$account->id}")
            ->assertRedirect(route('agents.show', $agent));

        $this->assertDatabaseMissing('agent_accounts', ['id' => $account->id]);
    }

    public function test_akun_milik_agent_lain_tidak_bisa_diubah(): void
    {
        $agent = Agent::factory()->create();
        $other = Agent::factory()->create();
        $platform = Platform::factory()->create();
        $account = $other->accounts()->create(['platform_id' => $platform->id, 'identifier' => 'lain@gmail.com']);

        $this->login()
            ->patch("/agents/{$agent->id}/accounts/{$account->id}", [
                'platform_id' => $platform->id,
                'identifier'  => 'bajakan@gmail.com',
            ])
            ->assertNotFound();

        $this->login()->delete("/agents/{$agent->id}/accounts/{$account->id}")->assertNotFound();

        $this->assertSame('lain@gmail.com', $account->refresh()->identifier);
    }

    public function test_auditor_tidak_bisa_ubah_akun(): void
    {
        $agent = Agent::factory()->create();

        $this->login(Role::Auditor)
            ->post("/agents/{$agent->id}/accounts", ['platform_id' => Platform::factory()->create()->id, 'identifier' => 'budi@gmail.com'])
            ->assertForbidden();

        $this->assertDatabaseCount('agent_accounts', 0);
    }
}