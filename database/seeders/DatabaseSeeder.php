<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlatformSeeder::class);

        // 1 akun per role, password default factory = "password" (dev only cuy)
        $admin = User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com', 'role' => Role::Admin]);
        $staff = User::factory()->create(['name' => 'Staff', 'email' => 'user@example.com', 'role' => Role::User]);
        User::factory()->create(['name' => 'Auditor', 'email' => 'auditor@example.com', 'role' => Role::Auditor]);

        $platforms = Platform::all();

        foreach (Agent::factory(15)->create(['created_by' => $admin->id]) as $agent) {
            // tiap agent punya akun di 3-7 platform
            foreach ($platforms->random(random_int(3, 7)) as $platform) {
                $agent->accounts()->create([
                    'platform_id' => $platform->id,
                    'identifier'  => fake()->unique()->safeEmail(),
                    'password'    => fake()->password(10),
                ]);

                Task::factory()->create([
                    'user_id'     => $staff->id,
                    'agent_id'    => $agent->id,
                    'platform_id' => $platform->id,
                ]);
            }
        }
    }
}
