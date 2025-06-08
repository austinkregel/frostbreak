<?php

namespace Database\Factories;

use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->word(),
            'code' => $this->faker->word(),
            'image' => $this->faker->word(),
            'author' => $this->faker->word(),
            'needs_additional_processing' => $this->faker->boolean(),
            'keywords' => $this->faker->words(4),
            'is_approved' => $this->faker->boolean(),
            'repository_url' => $this->faker->url(),
            'abandoned' => $this->faker->boolean(),
            'git_stars' => $this->faker->numberBetween(0, 1000),
            'git_forks' => $this->faker->numberBetween(0, 1000),
            'downloads' => $this->faker->numberBetween(0, 1000),
            'favers' => $this->faker->numberBetween(0, 1000),
            'git_watchers' => $this->faker->numberBetween(0, 1000),
            'last_updated_at' => $this->faker->dateTime(),
            'packagist_url' => $this->faker->url(),
            'latest_version_id' => null,
            'demo_url' => $this->faker->url(),
            'product_url' => $this->faker->url(),
        ];
    }
}
