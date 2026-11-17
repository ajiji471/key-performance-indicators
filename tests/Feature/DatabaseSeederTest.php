<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_memakai_kredensial_dari_config(): void
    {
        config([
            'seed.admin' => ['email' => 'boss@qa.test', 'password' => 'awal-admin'],
            'seed.auditor' => ['email' => 'audit@qa.test', 'password' => 'awal-auditor'],
            'seed.inputer1' => ['name' => 'Inputer Satu', 'email' => 'satu@qa.test', 'password' => 'awal-satu'],
            'seed.inputer2' => ['name' => 'Inputer Dua', 'email' => 'dua@qa.test', 'password' => 'awal-dua'],
        ]);

        $this->seed(DatabaseSeeder::class);

        foreach ([
            ['boss@qa.test', 'awal-admin', Role::Admin, 'Admin'],
            ['audit@qa.test', 'awal-auditor', Role::Auditor, 'Auditor'],
            ['satu@qa.test', 'awal-satu', Role::User, 'Inputer Satu'],
            ['dua@qa.test', 'awal-dua', Role::User, 'Inputer Dua'],
        ] as [$email, $password, $role, $name]) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "akun {$email} tidak dibuat");
            $this->assertTrue(Hash::check($password, $user->password));
            $this->assertSame($role, $user->role);
            $this->assertSame($name, $user->name);
        }
        $this->assertDatabaseCount('users', 4);
    }

    public function test_config_seed_fallback_ke_konvensi_tanpa_env(): void
    {
        $keys = [
            'SEED_ADMIN_EMAIL', 'SEED_ADMIN_PASSWORD',
            'SEED_AUDITOR_EMAIL', 'SEED_AUDITOR_PASSWORD',
            'SEED_INPUTER1_NAME', 'SEED_INPUTER1_EMAIL', 'SEED_INPUTER1_PASSWORD',
            'SEED_INPUTER2_NAME', 'SEED_INPUTER2_EMAIL', 'SEED_INPUTER2_PASSWORD',
        ];
        $saved = [];
        foreach ($keys as $key) {
            $saved[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        try {
            $config = require config_path('seed.php');
            $this->assertSame('admin@example.com', $config['admin']['email']);
            $this->assertSame('password', $config['admin']['password']);
            $this->assertSame('auditor@example.com', $config['auditor']['email']);
            $this->assertSame('password', $config['auditor']['password']);
            $this->assertSame('Ajiji', $config['inputer1']['name']);
            $this->assertSame('user@example.com', $config['inputer1']['email']);
            $this->assertSame('password', $config['inputer1']['password']);
            $this->assertSame('Gasti Nurina', $config['inputer2']['name']);
            $this->assertSame('gasti@example.com', $config['inputer2']['email']);
            $this->assertSame('password', $config['inputer2']['password']);
        } finally {
            foreach ($keys as $key) {
                [$env, $server, $put] = $saved[$key];
                if ($env !== null) {
                    $_ENV[$key] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$key] = $server;
                }
                if ($put !== false) {
                    putenv("{$key}={$put}");
                }
            }
        }
    }
}
