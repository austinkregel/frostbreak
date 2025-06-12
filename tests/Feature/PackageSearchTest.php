<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class PackageSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_array_if_no_packages_match_query()
    {
        $response = $this->postJson(route('kregel.root.plugin.search'), [
            'query' => 'nonexistent',
        ]);
        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_it_returns_matching_packages_for_query()
    {
        Package::factory()->create([
            'name' => 'AnotherPlugin',
            'keywords' => ['plugin'],
        ]);

        Package::factory()->create([
            'name' => 'SuperPlugin',
            'keywords' => ['plugin'],
        ]);
        Package::factory()->create([
            'name' => 'ThemePackage',
            'keywords' => ['theme'],
        ]);

        $response = $this->postJson(route('kregel.root.plugin.search'), [
            'query' => 'Super',
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'SuperPlugin']);
        $this->assertCount(1, $response->json());
    }

    public function test_it_limits_results_to_10_packages()
    {
        for ($i = 1; $i <= 15; $i++) {
            Package::factory()->create([
                'name' => "Plugin{$i}",
                'keywords' => ['plugin'],
            ]);
        }
        $response = $this->postJson(route('kregel.root.plugin.search'), [
            'query' => 'Plugin',
        ]);
        $response->assertStatus(200);
        $this->assertCount(10, $response->json());
    }
}
