<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_projects()
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->for($user, 'owner')->create();
        $response = $this->actingAs($user)
            ->get('/projects');
        $response->assertStatus(200);
        $response->assertSee(Project::first()->name);
    }

    public function test_user_can_create_project()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->post('/projects', [
                'name' => 'New Project',
                'description' => 'A test project',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('marketplace_projects', [
            'name' => 'New Project',
            'owner_id' => $user->id,
        ]);
    }

    public function test_user_can_update_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        $response = $this->actingAs($user)
            ->put("/projects/{$project->license_id}", [
                'name' => 'Updated Name',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('marketplace_projects', [
            'license_id' => $project->license_id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_delete_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create([
            'name' => 'Project to Delete',
            'license_id' => '123e4567-e89b-12d3-a456-426614174000',
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->license_id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('marketplace_projects', [
            'license_id' => $project->license_id,
        ]);
    }
}
