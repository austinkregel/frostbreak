<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;
use App\Models\Version;

class CoreUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2024-03-01 00:00:00'));
    }

    public function test_it_returns_no_updates_if_no_core_package_exists()
    {
        $response = $this->postJson('/marketplace/core/update', [
            'core' => '1.0.0',
            'build' => '1.0.0',
        ]);
        $response->assertStatus(200)
            ->assertJsonMissing(['coreUpdates']);
    }

    public function test_it_returns_no_updates_if_core_is_latest()
    {
        $core = Package::factory()->create(['code' => 'Winter.Storm']);
        Version::factory()->create([
            'package_id' => $core->id,
            'semantic_version' => '1.0.0',
            'released_at' => now(),
        ]);
        $response = $this->postJson('/marketplace/core/update', [
            'core' => 'Winter.Storm',
            'build' => '1.0.0',
        ]);
        $response->assertStatus(200)
            ->assertJsonMissing(['coreUpdates']);
    }

    public function test_it_returns_updates_if_core_is_behind()
    {
        $core = Package::factory()->create(['code' => 'Winter.Storm']);
        Version::factory()->create([
            'package_id' => $core->id,
            'semantic_version' => '1.0.0',
            'released_at' => '2024-01-01 00:00:00',
        ]);
        Version::factory()->create([
            'package_id' => $core->id,
            'semantic_version' => '1.1.0',
            'released_at' => '2024-02-01 00:00:00',
            'description' => 'Update 1.1.0',
        ]);
        $response = $this->postJson('/marketplace/core/update', [
            'core' => 'Winter.Storm',
            'build' => '1.0.0',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('core.updates', ['1.1.0' => 'Update 1.1.0']);
    }

    public function test_it_returns_all_updates_if_force_is_true()
    {
        $core = Package::factory()->create(['code' => 'Winter.Storm']);
        Version::factory()->create([
            'package_id' => $core->id,
            'semantic_version' => '1.0.0',
            'released_at' => '2024-01-01 00:00:00',
            'description' => 'Update 1.0.0',
        ]);
        Version::factory()->create([
            'package_id' => $core->id,
            'semantic_version' => '1.1.0',
            'released_at' => '2024-02-01 00:00:00',
            'description' => 'Update 1.1.0',
        ]);
        $response = $this->postJson('/marketplace/core/update', [
            'core' => 'Winter.Storm',
            'build' => '1.0.0',
            'force' => true,
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('core.updates', [
                '1.0.0' => 'Update 1.0.0',
                '1.1.0' => 'Update 1.1.0',
            ]);
    }
}
