<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'job_title' => 'Software Engineer',
            'company_name' => 'Tech Corp',
            'avatar_url' => 'https://example.com/avatar.png',
            'location' => 'Remote',
            'bio' => 'Building event networking tools.',
            'phone_number' => '+123456789',
            'is_first_timer' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/profile');

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.profile.job_title', 'Software Engineer');
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $this->getJson('/api/user/profile')->assertStatus(401);
    }
}

