<?php

namespace Tests\Feature;

use Illuminate\Contracts\Filesystem\Filesystem;
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
        $response = $this->postJson(route('kregel.root.plugin.get'), [
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
        $response = $this->postJson(route('kregel.root.plugin.get'), [
            'name' => 'test-plugin',
        ]);
        $response->assertStatus(404)
            ->assertSee('No distribution URL found for this package.');
    }


    public function test_it_returns_file_on_success()
    {
        $package = Package::factory()->create([
            'name' => 'test-plugin',
            'code' => 'test-plugin',
            'keywords' => ['plugin'],
            'needs_additional_processing' => false,
        ]);
        $fileContent = 'file-content';
        $version = Version::factory()->create([
            'package_id' => $package->id,
            'dist_url' => 'http://fake-url.com/file.zip',
            'semantic_version' => '1.0.0',
            'hash' => md5($fileContent),
        ]);
        Http::fake([
            'http://fake-url.com/file.zip' => function ($request) use ($fileContent) {
                return Http::response($fileContent, 200, [
                    'Content-Type' => 'application/zip',
                    'content-disposition' => 'attachment; filename="file.zip"',
                ]);
            },
        ]);

        // Mock the Filesystem get method
        $mock = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
        $mock->shouldReceive('get')
            ->with($version->getCacheLocation())
            ->andReturn($fileContent);
        $this->app->instance('filesystem', $mock);
        $this->app->instance(\Illuminate\Filesystem\Filesystem::class, $mock);

        $response = $this->postJson(route('kregel.root.plugin.get'), [
            'name' => 'test-plugin',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename="test-plugin-1.0.0.zip"');
    }
}
