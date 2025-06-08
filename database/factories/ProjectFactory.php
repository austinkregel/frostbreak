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
            'owner_type' => 'App\Models\User',
            'owner_id' => User::factory(), // Assuming a user with ID 1 exists
            'owner' => function (array $attributes) {
                return (User::find($attributes['owner_id']) ?: User::factory()->create(['id' => $attributes['owner_id']]))['name'];
            },
            'license_id' => $this->faker->uuid(),
        ];
    }
}
