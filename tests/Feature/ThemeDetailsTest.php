<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class ThemeDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_array_if_no_names_provided()
    {
        $response = $this->postJson(route('kregel.root.theme.details'), [
            'names' => [],
        ]);
        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_it_returns_empty_array_if_no_themes_found()
    {
        $response = $this->postJson(route('kregel.root.theme.details'), [
            'names' => ['not-a-theme'],
        ]);
        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_it_returns_themes_for_valid_names()
    {
        $theme1 = Package::factory()->create([
            'name' => 'theme-one',
            'keywords' => ['theme'],
            'needs_additional_processing' => false,
        ]);
        $theme2 = Package::factory()->create([
            'name' => 'theme-two',
            'keywords' => ['theme'],
            'needs_additional_processing' => false,
        ]);

        $response = $this->postJson(route('kregel.root.theme.details'), [
            'names' => ['theme-one', 'theme-two'],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'theme-one'])
            ->assertJsonFragment(['name' => 'theme-two']);
    }
}
