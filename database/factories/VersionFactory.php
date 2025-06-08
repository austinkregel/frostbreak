<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class VersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'semantic_version' => $this->faker->semver(),
            'hash' => sha1($this->faker->word()),
            'released_at' => $this->faker->dateTime(),
            'dist_url' => $this->faker->url(),
            'license' => "MIT",
            'requires' => $this->faker->words(),
            'requires_dev' => $this->faker->words(),
            'suggests' => $this->faker->words(),
            'provides' => $this->faker->words(),
            'conflicts' => $this->faker->words(),
            'replaces' => $this->faker->words(),
            'tags' => $this->faker->words(),
            'installation_commands' =>$this->faker->words(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
