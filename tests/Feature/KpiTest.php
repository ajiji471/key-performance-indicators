<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/kpi')->assertRedirect('/login');
    }

    public function test_semua_role_bisa_buka_halaman_kpi(): void
    {
        foreach (Role::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/kpi')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Kpi/Index'));
        }
    }
}
