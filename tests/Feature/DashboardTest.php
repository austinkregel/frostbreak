<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_projects_for_authenticated_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
        ]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    public function test_dashboard_shows_empty_when_no_projects()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        // Optionally assert that no projects are shown
    }
}

