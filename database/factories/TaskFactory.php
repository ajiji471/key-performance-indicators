<?php

namespace Database\Factories;

use App\Enums\TaskProgress;
use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_date'   => fake()->dateTimeBetween('-40 days', 'now')->format('Y-m-d'),
            'user_id'     => User::factory(),
            'agent_id'    => Agent::factory(),
            'platform_id' => Platform::factory(),
            'status'      => fake()->randomElement(TaskStatus::cases()),
            'task_status' => fake()->randomElement(TaskProgress::cases()),
            'note'        => null,
        ];
    }
}
