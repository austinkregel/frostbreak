<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class ThemePopularTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_top_10_popular_themes()
    {
        // Create 12 themes, only 10 should be returned
        for ($i = 1; $i <= 12; $i++) {
            Package::factory()->create([
                'name' => "Theme{$i}",
                'keywords' => ['theme'],
                'needs_additional_processing' => false,
                'downloads' => 100 - $i,
            ]);
        }
        $response = $this->postJson(route('kregel.root.theme.popular'));
        $response->assertStatus(200);
        $this->assertCount(10, $response->json());
    }

    public function test_it_excludes_themes_with_needs_additional_processing_true()
    {
        Package::factory()->create([
            'name' => 'should-be-excluded',
            'keywords' => ['theme'],
            'needs_additional_processing' => true,
        ]);
        $response = $this->postJson(route('kregel.root.theme.popular'));
        $response->assertStatus(200);
        $this->assertNotContains('should-be-excluded', array_column($response->json(), 'name'));
    }
}

