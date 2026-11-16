<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgentTargetTest extends TestCase
{
    use RefreshDatabase;

    private function login(Role $role): static
    {
        return $this->actingAs(User::factory()->create(['role' => $role]));
    }

    public function test_admin_bisa_update_target(): void
    {
        $this->login(Role::Admin)
            ->patch('/dashboard/agent-target', ['target' => 350])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'agent_target', 'value' => '350']);
    }

    public function test_dashboard_memuat_target_baru(): void
    {
        Setting::put('agent_target', 350);

        $this->login(Role::Admin)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('agent_target', 350));
    }

    public function test_user_dan_auditor_ditolak(): void
    {
        foreach ([Role::User, Role::Auditor] as $role) {
            $this->login($role)
                ->patch('/dashboard/agent-target', ['target' => 350])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_guest_redirect_ke_login(): void
    {
        $this->patch('/dashboard/agent-target', ['target' => 350])
            ->assertRedirect();

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_target_invalid_ditolak(): void
    {
        foreach ([0, -5, 'abc', '', 1000001] as $bad) {
            $this->login(Role::Admin)
                ->patch('/dashboard/agent-target', ['target' => $bad])
                ->assertSessionHasErrors('target');
        }

        $this->assertDatabaseCount('settings', 0);
    }
}
