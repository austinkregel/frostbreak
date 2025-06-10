<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class ThemeDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_404_if_theme_not_found()
    {
        $response = $this->postJson(route('kregel.root.theme.detail'), [
            'name' => 'non-existent-theme',
        ]);
        $response->assertStatus(404);
    }

    public function test_it_returns_theme_details_on_success()
    {
        $theme = Package::factory()->create([
            'name' => 'test-theme',
            'keywords' => ['theme'],
            'needs_additional_processing' => false,
        ]);
        $response = $this->postJson(route('kregel.root.theme.detail'), [
            'name' => 'test-theme',
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'test-theme',
            ]);
    }
}
