<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_project_details()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        $this->actingAs($user);
        $response = $this->get(route('project.show', $project));
        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    public function test_store_creates_project()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->post(route('projects.store'), [
            'name' => 'Test Project',
        ]);

        $project = Project::where('name', 'Test Project')->first();
        $response->assertRedirect(route('project.show', ['project' => $project->license_id]));
        $this->assertDatabaseHas('marketplace_projects', [
            'name' => 'Test Project',
            'owner_id' => $user->id,
        ]);
    }

    public function test_add_plugin_to_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        $plugin = Package::factory()->create([
            'keywords' => ['plugin'],
        ]);
        $this->actingAs($user);
        $response = $this->post(route('project.add-plugin', $project), [
            'id' => $plugin->id,
        ]);
        $response->assertRedirect(route('project.show', ['project' => $project->license_id]));
        $project->refresh();
        $this->assertTrue($project->plugins()->where('marketplace_packages.id', $plugin->id)->exists());
    }

    public function test_add_theme_to_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        $theme = Package::factory()->create([
            'keywords' => ['theme'],
        ]);
        $this->actingAs($user);
        $response = $this->post(route('project.add-theme', $project), [
            'id' => $theme->id,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertTrue($project->themes()->where('marketplace_packages.id', $theme->id)->exists());
    }

    public function test_cannot_add_plugin_to_another_users_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $plugin = Package::factory()->create(['keywords' => ['plugin']]);
        $this->actingAs($otherUser);
        $response = $this->postJson(route('project.add-plugin', $project), [
            'id' => $plugin->id,
        ]);
        $response->assertForbidden();
        $this->assertFalse($project->plugins()->where('marketplace_packages.id', $plugin->id)->exists());
    }

    public function test_cannot_remove_plugin_from_another_users_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $plugin = Package::factory()->create(['keywords' => ['plugin']]);
        $project->plugins()->attach($plugin->id);
        $this->actingAs($otherUser);
        $response = $this->postJson(route('project.remove-plugin', $project), [
            'id' => $plugin->id,
        ]);
        $response->assertForbidden();
        $this->assertTrue($project->plugins()->where('marketplace_packages.id', $plugin->id)->exists());
    }

    public function test_cannot_add_theme_to_another_users_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $theme = Package::factory()->create(['keywords' => ['theme']]);
        $this->actingAs($otherUser);
        $response = $this->postJson(route('project.add-theme', $project), [
            'id' => $theme->id,
        ]);
        $response->assertForbidden();
        $this->assertFalse($project->themes()->where('marketplace_packages.id', $theme->id)->exists());
    }

    public function test_cannot_remove_theme_from_another_users_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $theme = Package::factory()->create(['keywords' => ['theme']]);
        $project->themes()->attach($theme->id);
        $this->actingAs($otherUser);
        $response = $this->postJson(route('project.remove-theme', $project), [
            'id' => $theme->id,
        ]);
        $response->assertForbidden();
        $this->assertTrue($project->themes()->where('marketplace_packages.id', $theme->id)->exists());
    }
}
