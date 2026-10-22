<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'recovery_email'     => fake()->safeEmail(),
            'password'           => fake()->password(10),
            'birth_date'         => fake()->date(),
            'address'            => fake()->address(),
            'latitude'           => fake()->latitude(-8, -6),
            'longitude'          => fake()->longitude(105, 108),
            'job'                => fake()->jobTitle(),
            'hobby'              => fake()->word(),
            'context'            => fake()->sentence(),
            'device_user'        => fake()->userName(),
            'device_number'      => fake()->numerify('08##########'),
            'device_released_at' => fake()->date(),
            'created_by'         => User::factory(),
        ];
    }
}
