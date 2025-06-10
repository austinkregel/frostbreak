<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class PackagePopularTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_top_10_popular_packages()
    {
        // Create 12 packages, only 10 should be returned
        $indexes = range(1, 12);
        foreach ($indexes as $index) {
            Package::factory()->create([
                'code' => "My.Plugin{$index}",
                'keywords' => ['plugin'],
                'needs_additional_processing' => false,
                'downloads' => $index, // Assuming popularity is a field that determines the order
            ]);
        }

        $response = $this->postJson(route('kregel.root.plugin.popular'));
        $response->assertStatus(200);
        $this->assertCount(10, $response->json());

        $this->assertEquals('My.Plugin12', $response->json()[0]['code']);
    }

    /** @test */
    public function it_excludes_packages_with_needs_additional_processing_true()
    {
        Package::factory()->create([
            'code' => 'should-be-excluded',
            'keywords' => ['plugin'],
            'needs_additional_processing' => true,
        ]);
        $response = $this->postJson(route('kregel.root.plugin.popular'));
        $response->assertStatus(200);
        $this->assertNotContains('should-be-excluded', array_column($response->json(), 'code'));
    }
}

