<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'price' => fake()->randomFloat(2, 99, 2000), 'duration_days' => fake()->randomElement([30, 90, 365]), 'description' => fake()->sentence(), 'features' => [fake()->sentence()], 'is_active' => true];
    }
}
