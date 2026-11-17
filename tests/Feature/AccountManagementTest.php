<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function login(Role $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user);

        return $user;
    }

    public function test_admin_melihat_halaman_accounts(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'name' => 'A Admin']);
        $this->actingAs($admin);
        $other = User::factory()->create(['name' => 'Zed']);

        $this->get('/accounts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounts/Index')
                ->has('users', 2)
                ->where('users.0.id', $admin->id)
                ->where('users.1.id', $other->id)
                ->missing('users.0.password'));
    }

    public function test_user_dan_auditor_ditolak_masuk_accounts(): void
    {
        foreach ([Role::User, Role::Auditor] as $role) {
            $this->login($role);
            $this->get('/accounts')->assertForbidden();
        }
    }

    public function test_guest_accounts_redirect_login(): void
    {
        $this->get('/accounts')->assertRedirect('/login');
    }

    public function test_admin_bisa_membuat_user(): void
    {
        $this->login(Role::Admin);

        $this->post('/accounts', [
            'name' => 'New Inputer',
            'email' => 'inputer@example.com',
            'password' => 'secret-password',
            'role' => 'user',
        ])->assertRedirect()->assertSessionHas('success');

        $created = User::where('email', 'inputer@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame('New Inputer', $created->name);
        $this->assertSame(Role::User, $created->role);
        $this->assertTrue($created->is_active);
        $this->assertTrue(Hash::check('secret-password', $created->password));
    }

    public function test_store_validasi(): void
    {
        $this->login(Role::Admin);
        User::factory()->create(['email' => 'taken@example.com']);

        $cases = [
            ['name' => '', 'email' => 'a@example.com', 'password' => 'secret-password', 'role' => 'user'],
            ['name' => 'A', 'email' => 'taken@example.com', 'password' => 'secret-password', 'role' => 'user'],
            ['name' => 'A', 'email' => 'bukan-email', 'password' => 'secret-password', 'role' => 'user'],
            ['name' => 'A', 'email' => 'a@example.com', 'password' => 'short', 'role' => 'user'],
            ['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-password', 'role' => 'superadmin'],
        ];

        foreach ($cases as $payload) {
            $this->post('/accounts', $payload)->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('users', 2);
    }

    public function test_user_dan_auditor_tidak_bisa_membuat_user(): void
    {
        foreach ([Role::User, Role::Auditor] as $role) {
            $this->login($role);
            $this->post('/accounts', [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'secret-password',
                'role' => 'user',
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_bisa_mengubah_role(): void
    {
        $this->login(Role::Admin);
        $target = User::factory()->create(['role' => Role::User]);

        $this->patch("/accounts/{$target->id}", ['role' => 'auditor'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Role::Auditor, $target->fresh()->role);
    }

    public function test_admin_bisa_nonaktifkan_dan_aktifkan_user(): void
    {
        $this->login(Role::Admin);
        $target = User::factory()->create(['is_active' => true]);

        $this->patch("/accounts/{$target->id}", ['is_active' => false])->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);

        $this->patch("/accounts/{$target->id}", ['is_active' => true])->assertRedirect();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_bisa_reset_password(): void
    {
        $this->login(Role::Admin);
        $target = User::factory()->create();
        $oldHash = $target->password;

        $this->patch("/accounts/{$target->id}", ['password' => 'new-secret-password'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $target->fresh();
        $this->assertNotSame($oldHash, $fresh->password);
        $this->assertTrue(Hash::check('new-secret-password', $fresh->password));
        $this->assertFalse(Hash::check('password', $fresh->password));
    }

    public function test_admin_aktif_terakhir_tidak_bisa_diturunkan_atau_dinonaktifkan(): void
    {
        $admin = $this->login(Role::Admin);

        $this->patch("/accounts/{$admin->id}", ['role' => 'user'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(Role::Admin, $admin->fresh()->role);

        $this->patch("/accounts/{$admin->id}", ['is_active' => false])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_dengan_dua_admin_penurunan_role_diizinkan(): void
    {
        $admin = $this->login(Role::Admin);
        $second = User::factory()->create(['role' => Role::Admin]);

        $this->patch("/accounts/{$second->id}", ['role' => 'user'])->assertRedirect();

        $this->assertSame(Role::User, $second->fresh()->role);
        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_update_validasi(): void
    {
        $this->login(Role::Admin);
        $target = User::factory()->create();

        $this->patch("/accounts/{$target->id}", ['role' => 'superadmin'])->assertSessionHasErrors('role');
        $this->patch("/accounts/{$target->id}", ['password' => 'short'])->assertSessionHasErrors('password');
        $this->patch("/accounts/{$target->id}", ['is_active' => 'bukan-boolean'])->assertSessionHasErrors('is_active');
    }

    public function test_user_nonaktif_tidak_bisa_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'secret-password',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_nonaktif_dengan_sesi_ditolak_di_rute_terlindungi(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);

        $this->actingAs($inactive)->get('/kpi')->assertForbidden();
    }
}
