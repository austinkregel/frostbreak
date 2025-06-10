<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Package;

class PluginThemeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugin_search_returns_results_with_highlighted_query()
    {
        Package::factory()->create(['name' => 'HighlightPlugin', 'keywords' => ['plugin']]);
        $response = $this->postJson(route('kregel.root.plugin.search'), ['query' => 'Highlight']);
        $response->assertStatus(200);
        $this->assertStringContainsString('Highlight', $response->getContent());
    }

    public function test_theme_search_returns_results_with_highlighted_query()
    {
        Package::factory()->create(['name' => 'HighlightTheme', 'keywords' => ['theme']]);
        $response = $this->postJson(route('kregel.root.theme.search'), ['query' => 'Highlight']);
        $response->assertStatus(200);
        $this->assertStringContainsString('Highlight', $response->getContent());
    }

    public function test_search_returns_empty_for_no_results()
    {
        $response = $this->postJson(route('kregel.root.plugin.search'), ['query' => 'NoMatch']);
        $response->assertStatus(200);
        $this->assertEquals([], $response->json());
    }
}
