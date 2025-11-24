<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
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
            'linkedin_url' => 'https://www.linkedin.com/in/test-user',
            'location' => 'Remote',
            'bio' => 'Building event networking tools.',
            'phone_number' => '1234567890',
            'is_first_timer' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/profile');

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.profile.job_title', 'Software Engineer')
            ->assertJsonPath('user.qr_payload', $user->qrPayload());
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $this->getJson('/api/user/profile')->assertStatus(401);
    }

    public function test_authenticated_user_can_update_profile_with_tags(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'job_title' => 'Developer Advocate',
            'company_name' => 'API Hub',
            'avatar_url' => 'https://example.com/old.png',
            'linkedin_url' => 'https://www.linkedin.com/in/old-user',
            'location' => 'Remote',
            'bio' => 'Helping devs connect.',
            'phone_number' => '0001112222',
            'is_first_timer' => false,
            'tags' => ['community'],
        ]);

        $payload = [
            'name' => 'Updated User',
            'job_title' => 'Product Lead',
            'company_name' => 'API Hub',
            'avatar_url' => 'https://example.com/new.png',
            'linkedin_url' => 'https://www.linkedin.com/in/new-user',
            'location' => 'NYC',
            'bio' => 'Building connections.',
            'phone_number' => '1234567890',
            'is_first_timer' => true,
            'tags' => ['product', 'networking'],
        ];

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/user/profile', $payload);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Updated User')
            ->assertJsonPath('user.profile.linkedin_url', 'https://www.linkedin.com/in/new-user')
            ->assertJsonPath('user.profile.tags', ['product', 'networking']);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
        ]);

        $storedTags = DB::table('user_profiles')
            ->where('user_id', $user->id)
            ->value('tags');

        $this->assertSame(['product', 'networking'], json_decode($storedTags, true));
    }
}
