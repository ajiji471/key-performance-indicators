<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/agents')->assertRedirect('/login');
        $this->post('/agents', $this->payload())->assertRedirect('/login');
    }

    public function test_semua_role_bisa_lihat_daftar_agent(): void
    {
        Agent::factory()->create(['name' => 'Budi']);

        foreach (Role::cases() as $role) {
            $this->login($role)
                ->get('/agents')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Agents/Index')
                    ->has('agents.data', 1)
                );
        }
    }

    public function test_search_dan_sort_agent(): void
    {
        Agent::factory()->create(['name' => 'Budi']);
        Agent::factory()->create(['name' => 'Andi']);

        $this->login()
            ->get('/agents?sort=desc')
            ->assertInertia(fn (Assert $page) => $page->where('agents.data.0.name', 'Budi'));

        $this->login()
            ->get('/agents?search=And')
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
        $agent->accounts()->create(['platform_id' => \App\Models\Platform::factory()->create()->id, 'identifier' => 'budi@gmail.com']);

        $this->login(Role::Auditor)
            ->get("/agents/{$agent->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agents/Show')
                ->has('agent.accounts', 1)
                ->where('agent.accounts.0.identifier', 'budi@gmail.com')
            );
    }

    public function test_update_tanpa_password_tidak_menimpa_password_lama(): void
    {
        $agent = Agent::factory()->create(['password' => 'password-lama']);

        $this->login()
            ->patch("/agents/{$agent->id}", ['name' => 'Budi Baru', 'email' => 'baru@example.com'])
            ->assertRedirect(route('agents.show', $agent));

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
            ->assertRedirect(route('agents.index'));

        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
    }

    public function test_password_agent_tidak_bocor(): void
    {
        $agent = Agent::factory()->create(['password' => 'rahasia-banget-123']);

        $list = $this->login()->get('/agents')->getContent();
        $detail = $this->login()->get("/agents/{$agent->id}")->getContent();

        $this->assertStringNotContainsString('rahasia-banget-123', $list);
        $this->assertStringNotContainsString('rahasia-banget-123', $detail);
    }
}