<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class PackageDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_404_if_package_not_found()
    {
        $response = $this->postJson(route('kregel.root.plugin.detail'), [
            'name' => 'non-existent-package',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_package_details_on_success()
    {
        $package = Package::factory()->create([
            'code' => 'My.Plugin',
            'keywords' => ['plugin'],
        ]);

        $response = $this->postJson(route('kregel.root.plugin.detail'), [
            'name' => 'My.Plugin',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'code' => 'My.Plugin',
            ]);
    }
}
