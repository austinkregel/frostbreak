<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class ThemeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_array_if_no_themes_match_query()
    {
        $response = $this->postJson(route('kregel.root.theme.search'), [
            'query' => 'nonexistent',
        ]);
        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_it_returns_matching_themes_for_query()
    {
        Package::factory()->create([
            'code' => 'AnotherTheme',
            'keywords' => ['theme'],
        ]);
        Package::factory()->create([
            'code' => 'SuperTheme',
            'keywords' => ['theme'],
        ]);
        Package::factory()->create([
            'code' => 'PluginPackage',
            'keywords' => ['plugin'],
        ]);

        $response = $this->postJson(route('kregel.root.theme.search'), [
            'query' => 'Super',
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'SuperTheme']);
        $this->assertCount(1, $response->json());
    }

    public function test_it_limits_results_to_10_themes()
    {
        for ($i = 1; $i <= 15; $i++) {
            Package::factory()->create([
                'code' => "Theme{$i}",
                'keywords' => ['theme'],
            ]);
        }
        $response = $this->postJson(route('kregel.root.theme.search'), [
            'query' => 'Theme',
        ]);
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json()));
    }
}
