<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug'   => fake()->unique()->slug(2),
            'name'   => fake()->unique()->word(),
            'is_kpi' => true,
            'sort'   => fake()->numberBetween(1, 99),
        ];
    }
}
