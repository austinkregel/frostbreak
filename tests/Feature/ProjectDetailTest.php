<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;
use App\Models\Package;

class ProjectDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_400_if_id_invalid()
    {
        $response = $this->postJson(route('kregel.root.project.detail'), [
            'id' => 9999,
        ]);
        $response->assertStatus(400);
        $response->assertSee('Invalid Project License Key; please use the UUID in the URL, not the project id');
    }

    public function test_it_returns_400_if_project_not_found()
    {
        $response = $this->postJson(route('kregel.root.project.detail'), [
            'id' => '1803248af-ff34809af-380a83-ksfae',
        ]);
        $response->assertStatus(400);
        $response->assertSee('Invalid Project License Key; project does not exist');
    }

    public function test_it_returns_project_with_plugins_and_themes()
    {
        $user = \App\Models\User::factory()->create();

        $project = Project::factory()->create([
            'name' => 'Test Project',
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'license_id' => '123e4567-e89b-12d3-a456-426614174000', // Example UUID
        ]);
        $plugin = Package::factory()->create(['keywords' => ['plugin']]);
        $theme = Package::factory()->create(['keywords' => ['theme']]);
        $project->plugins()->attach($plugin);
        $project->themes()->attach($theme);

        $response = $this->actingAs($user)->postJson(route('kregel.root.project.detail'), [
            'id' => $project->license_id,
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['license_id' => $project->license_id])
            ->assertJsonFragment(['id' => $plugin->id])
            ->assertJsonFragment(['id' => $theme->id]);
    }
}
