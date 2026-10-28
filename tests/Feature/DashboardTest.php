<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
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

    public function test_guest_bisa_melihat_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.user', null)
            );
    }

    public function test_flash_sukses_dibagikan_ke_dashboard(): void
    {
        $this->login(Role::Admin)
            ->withSession(['success' => 'Agent added.'])
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Agent added.'));
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

    public function test_chart_today_ikut_status_akun_dan_chart_bulan_dari_task(): void
    {
        $platform = Platform::factory()->create();

        $agent = Agent::factory()->create();
        $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'agent@example.com',
            'status'      => AccountStatus::LoggedIn,
        ]);

        $recovered = Agent::factory()->create();
        $recovered->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'recovered@example.com',
            'status'      => AccountStatus::Recovered,
        ]);

        // task hari ini registered: dipakai month/all, bukan today
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
                ->where('stats.today.0.logged_in', 1)
                ->where('stats.today.0.recovered', 1)
                ->where('stats.today.0.registered', 0)
                ->where('stats.month.0.registered', 1)
                ->where('stats.month.0.logged_in', 0)
                ->where('stats.all.0.registered', 1)
                ->where('stats.all.0.logged_in', 1)
            );
    }

    public function test_status_platform_di_tabel_ikut_status_akun(): void
    {
        $platform = Platform::factory()->create();
        $agent = Agent::factory()->create();

        $agent->accounts()->create([
            'platform_id' => $platform->id,
            'identifier'  => 'rogelio@example.com',
            'status'      => AccountStatus::LoggedIn,
        ]);

        // task lama tidak boleh menimpa status akun di tabel
        Task::factory()->create([
            'agent_id'    => $agent->id,
            'platform_id' => $platform->id,
            'status'      => TaskStatus::Trouble,
            'task_date'   => today()->toDateString(),
        ]);

        $this->login(Role::Admin)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('agents.data.0.platforms.0.status', 'logged_in')
                ->where('agents.data.0.created_at', $agent->created_at->toDateString())
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
