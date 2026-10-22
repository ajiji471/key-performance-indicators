<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function login(Role $role): static
    {
        return $this->actingAs(User::factory()->create(['role' => $role]));
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_semua_role_bisa_buka_dashboard(): void
    {
        foreach (Role::cases() as $role) {
            $this->login($role)
                ->get('/dashboard')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
        }
    }

    public function test_stats_hitung_distinct_agent_per_status(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create();

        // agent sama 2x registered hari ini = tetap 1
        Task::factory()->count(2)->create([
            'agent_id'    => $agent->id,
            'platform_id' => $platform->id,
            'status'      => TaskStatus::Registered,
            'task_date'   => today()->toDateString(),
        ]);

        // logged_in 40 hari lalu: masuk "semua" saja
        Task::factory()->create([
            'agent_id'    => $agent->id,
            'platform_id' => $platform->id,
            'status'      => TaskStatus::LoggedIn,
            'task_date'   => today()->subDays(40)->toDateString(),
        ]);

        $this->login(Role::Admin)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.today.registered', 1)
                ->where('stats.today.logged_in', 0)
                ->where('stats.month.logged_in', 0)
                ->where('stats.all.registered', 1)
                ->where('stats.all.logged_in', 1)
            );
    }

    public function test_search_dan_sort_agent(): void
    {
        Agent::factory()->create(['name' => 'Budi']);
        Agent::factory()->create(['name' => 'Andi']);

        $this->login(Role::Admin)
            ->get('/dashboard?sort=desc')
            ->assertInertia(fn (Assert $page) => $page->where('agents.data.0.name', 'Budi'));

        $this->login(Role::Admin)
            ->get('/dashboard?search=And')
            ->assertInertia(fn (Assert $page) => $page->has('agents.data', 1));
    }

    public function test_password_agent_tidak_bocor_di_list(): void
    {
        Agent::factory()->create(['password' => 'rahasia-banget-123']);

        $response = $this->login(Role::Auditor)->get('/dashboard');

        $this->assertStringNotContainsString('rahasia-banget-123', $response->getContent());
    }
}
