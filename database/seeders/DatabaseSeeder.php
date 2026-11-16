<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlatformSeeder::class);

        foreach ([['Admin', 'admin', Role::Admin], ['Auditor', 'auditor', Role::Auditor]] as [$name, $key, $role]) {
            User::factory()->create([
                'name' => $name,
                'email' => config("seed.{$key}.email"),
                'password' => Hash::make(config("seed.{$key}.password")),
                'role' => $role,
            ]);
        }

        foreach (['inputer1', 'inputer2'] as $key) {
            User::factory()->create([
                'name' => config("seed.{$key}.name"),
                'email' => config("seed.{$key}.email"),
                'password' => Hash::make(config("seed.{$key}.password")),
                'role' => Role::User,
            ]);
        }

        $this->call(AgentSeeder::class);
    }
}
