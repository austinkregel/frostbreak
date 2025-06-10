<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;
use App\Models\Package;

class ProjectDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_404_if_project_not_found()
    {
        $response = $this->postJson(route('kregel.root.project.detail'), [
            'id' => 9999,
        ]);
        $response->assertStatus(404);
    }

    public function test_it_returns_project_with_plugins_and_themes()
    {
        $project = Project::factory()->create();
        $plugin = Package::factory()->create(['keywords' => ['plugin']]);
        $theme = Package::factory()->create(['keywords' => ['theme']]);
        $project->plugins()->attach($plugin);
        $project->themes()->attach($theme);

        $response = $this->postJson(route('kregel.root.project.detail'), [
            'id' => $project->id,
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $project->id])
            ->assertJsonFragment(['id' => $plugin->id])
            ->assertJsonFragment(['id' => $theme->id]);
    }
}

