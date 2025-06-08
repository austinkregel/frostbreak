<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create([
            'name' => 'Old Name',
            'email_verified_at' => now(),
        ]));

        $response = $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ]);
        $response->assertFound();

        $this->assertEquals('Test Name', $user->refresh()->name);
        $this->assertEquals('test@example.com', $user->refresh()->email);
    }
}
