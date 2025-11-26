<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeCompletion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_today_challenges_with_status_and_progress(): void
    {
        $user = User::factory()->create();
        CarbonImmutable::setTestNow('2024-10-01 09:00:00');

        $challenge = Challenge::create([
            'code' => 'DAILY_SESSIONS_2',
            'title' => 'Attend 2 sessions',
            'description' => null,
            'points' => 200,
            'requirements' => ['session_attended' => 2],
            'frequency' => 'daily',
            'applies_to_day' => 1,
            'is_enabled' => true,
            'max_completions_per_user_per_day' => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/challenges/today');

        $response->assertOk();
        $payload = $response->json('challenges')[0];
        $this->assertEquals($challenge->code, $payload['code']);
        $this->assertEquals('in_progress', $payload['status']);
        $this->assertEquals(['current' => 0, 'target' => 2], $payload['progress']);
    }

    public function test_history_lists_completions(): void
    {
        $user = User::factory()->create();
        CarbonImmutable::setTestNow('2024-10-02 10:00:00');

        $challenge = Challenge::create([
            'code' => 'DAILY_BOOTH_3',
            'title' => 'Visit 3 booths',
            'description' => null,
            'points' => 75,
            'requirements' => ['sponsor_booth_visited' => 3],
            'frequency' => 'daily',
            'is_enabled' => true,
            'max_completions_per_user_per_day' => 1,
        ]);

        ChallengeCompletion::create([
            'challenge_id' => $challenge->id,
            'user_id' => $user->id,
            'event_day' => '2024-10-01',
            'awarded_points' => 75,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/challenges/history');

        $response->assertOk();
        $this->assertEquals($challenge->code, $response->json('data')[0]['code']);
        $this->assertEquals(75, $response->json('data')[0]['points']);
    }
}
