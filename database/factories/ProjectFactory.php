<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'owner_type' => 'App\\Models\\User',
            'owner_id' => User::factory(),
            'owner' => $this->faker->name(),
            'license_id' => $this->faker->uuid(),
        ];
    }
}
