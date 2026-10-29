<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlatformSeeder::class);

        // 1 akun per role, password default factory = "password" (dev only cuy)
        User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com', 'role' => Role::Admin]);
        User::factory()->create(['name' => 'Staff', 'email' => 'user@example.com', 'role' => Role::User]);
        User::factory()->create(['name' => 'Auditor', 'email' => 'auditor@example.com', 'role' => Role::Auditor]);

        $this->call(AgentSeeder::class);
    }
}
