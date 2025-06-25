<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class PackageDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_array_if_no_names_provided()
    {
        $response = $this->postJson(route('kregel.root.plugin.details'), [
            'names' => [],
        ]);

        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_it_returns_empty_array_if_no_packages_found()
    {
        $response = $this->postJson(route('kregel.root.plugin.details'), [
            'names' => ['not-a-package'],
        ]);

        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_it_returns_packages_for_valid_names()
    {
        $package1 = Package::factory()->create([
            'code' => 'My.PluginOne',
            'keywords' => ['plugin', 'wintercms', 'winter'],
            'needs_additional_processing' => false,
        ]);
        $package2 = Package::factory()->create([
            'code' => 'My.PluginTwo',
            'keywords' => ['plugin', 'wintercms', 'winter'],
            'needs_additional_processing' => false,
        ]);

        $response = $this->postJson(route('kregel.root.plugin.details'), [
            'names' => ['My.PluginOne', 'My.PluginTwo'],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'My.PluginOne'])
            ->assertJsonFragment(['code' => 'My.PluginTwo']);
    }
}
