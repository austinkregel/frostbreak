<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Package;
use App\Models\Version;

class PackageGetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_404_if_package_not_found()
    {
        $response = $this->postJson('/marketplace/plugin/get', [
            'name' => 'non-existent-package',
        ]);
        $response->assertStatus(404);
    }

    public function test_it_returns_404_if_no_distribution_url_found()
    {
        $package = Package::factory()->create([
            'code' => 'test-plugin',
            'keywords' => ['plugin'],
            'needs_additional_processing' => false,
        ]);
        Version::factory()->create([
            'package_id' => $package->id,
            'dist_url' => null,
        ]);
        $response = $this->postJson('/marketplace/plugin/get', [
            'name' => 'test-plugin',
        ]);
        $response->assertStatus(404)
            ->assertSee('No distribution URL found for this package.');
    }

    public function test_it_returns_500_if_dist_url_head_fails()
    {
        $package = Package::factory()->create([
            'code' => 'test-plugin',
            'keywords' => ['plugin'],
            'needs_additional_processing' => false,
        ]);
        $version = Version::factory()->create([
            'package_id' => $package->id,
            'dist_url' => 'http://fake-url.com/file.zip',
        ]);
        Http::fake([
            'http://fake-url.com/file.zip' => function ($request) {
                if ($request->method() === 'HEAD') {
                    return Http::response(null, 500);
                }
                return Http::response('file-content', 200);
            },
        ]);
        $response = $this->postJson('/marketplace/plugin/get', [
            'name' => 'test-plugin',
        ]);
        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Failed to fetch video headers']);
    }

    public function test_it_returns_file_on_success()
    {
        $package = Package::factory()->create([
            'code' => 'test-plugin',
            'keywords' => ['plugin'],
            'needs_additional_processing' => false,
        ]);
        $version = Version::factory()->create([
            'package_id' => $package->id,
            'dist_url' => 'http://fake-url.com/file.zip',
        ]);
        Http::fake([
            'http://fake-url.com/file.zip' => function ($request) {
                if ($request->method() === 'HEAD') {
                    return Http::response('', 200, [
                        'Content-Type' => 'application/zip',
                        'content-disposition' => 'attachment; filename="file.zip"',
                    ]);
                }
                return Http::response('file-content', 200, [
                    'Content-Type' => 'application/zip',
                    'content-disposition' => 'attachment; filename="file.zip"',
                ]);
            },
        ]);
        $response = $this->postJson('/marketplace/plugin/get', [
            'name' => 'test-plugin',
        ]);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $response->assertHeader('Content-Disposition', 'attachment; filename="file.zip"');
    }
}
