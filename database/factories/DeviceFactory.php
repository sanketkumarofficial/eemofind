<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'name' => fake()->word().' Tracker', 'imei' => fake()->unique()->numerify('###############'), 'device_type' => fake()->randomElement(['phone', 'gps_tracker', 'watch']), 'model' => fake()->bothify('EF-###'), 'firmware_version' => '1.0.0', 'sim_number' => fake()->numerify('##########'), 'is_enabled' => true];
    }
}
