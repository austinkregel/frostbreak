<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;
use App\Models\Version;

class CoreChangelogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_changelog_returns_versions_for_winter_storm_package()
    {
        $package = Package::factory()->create([
            'code' => 'Winter.Storm',
        ]);
        Version::factory()->create([
            'package_id' => $package->id,
            'semantic_version' => 'main-1.0.0',
            'description' => 'Initial release',
            'released_at' => now(),
        ]);
        Version::factory()->create([
            'package_id' => $package->id,
            'semantic_version' => 'main-1.1.0',
            'description' => 'Second release',
            'released_at' => now()->addDay(),
        ]);

        $response = $this->getJson(route('kregel.root.changelog', ['branch' => 'main']));
        $response->assertStatus(200)
            ->assertJsonFragment([
                'build' => 'main-1.1.0',
                'description' => 'Second release',
            ])
            ->assertJsonFragment([
                'build' => 'main-1.0.0',
                'description' => 'Initial release',
            ]);
    }
}

