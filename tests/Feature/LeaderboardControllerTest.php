<?php

namespace Tests\Feature;

use App\Models\PointsLog;
use App\Models\User;
use App\Models\UserConnection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_returns_ranked_leaders_with_connection_flag(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User']);
        $viewer->profile()->create([
            'job_title' => 'Viewer Role',
            'company_name' => 'Viewer Co',
        ]);

        $connected = User::factory()->create(['name' => 'Connected User']);
        $connected->profile()->create([
            'job_title' => 'Connected Role',
            'company_name' => 'Connected Co',
        ]);

        $other = User::factory()->create(['name' => 'Other User']);
        $other->profile()->create([
            'job_title' => 'Other Role',
            'company_name' => 'Other Co',
        ]);

        // Link viewer to the first leader to exercise the connected badge.
        UserConnection::factory()->create([
            'user_id' => $viewer->id,
            'attendee_id' => $connected->id,
        ]);

        PointsLog::factory()->create(['user_id' => $connected->id, 'points' => 120]);
        PointsLog::factory()->create(['user_id' => $other->id, 'points' => 60]);
        PointsLog::factory()->create(['user_id' => $viewer->id, 'points' => 90]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/leaderboard?period=30d&limit=10');

        $response->assertOk()
            ->assertJsonPath('period', '30d')
            ->assertJsonCount(3, 'leaders')
            ->assertJsonPath('leaders.0.name', 'Connected User')
            ->assertJsonPath('leaders.0.rank', 1)
            ->assertJsonPath('leaders.0.points', 120)
            ->assertJsonPath('leaders.0.connected', true)
            ->assertJsonPath('leaders.1.name', 'Viewer User')
            ->assertJsonPath('leaders.1.rank', 2)
            ->assertJsonPath('leaders.1.points', 90)
            ->assertJsonPath('leaders.1.connected', false)
            ->assertJsonPath('leaders.2.name', 'Other User')
            ->assertJsonPath('leaders.2.rank', 3)
            ->assertJsonPath('leaders.2.points', 60)
            ->assertJsonPath('leaders.2.connected', false)
            ->assertJsonPath('viewer.name', 'Viewer User')
            ->assertJsonPath('viewer.rank', 2)
            ->assertJsonPath('viewer.points', 90)
            ->assertJsonPath('viewer.connected', true);
    }

    public function test_leaderboard_can_filter_to_connected_only_and_24h_period(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer']);
        $viewer->profile()->create([
            'job_title' => 'Viewer Role',
            'company_name' => 'Viewer Co',
        ]);

        $friend = User::factory()->create(['name' => 'Friend']);
        $friend->profile()->create([
            'job_title' => 'Friend Role',
            'company_name' => 'Friend Co',
        ]);

        $stranger = User::factory()->create(['name' => 'Stranger']);
        $stranger->profile()->create([
            'job_title' => 'Stranger Role',
            'company_name' => 'Stranger Co',
        ]);

        // Viewer is connected to friend only.
        UserConnection::factory()->create([
            'user_id' => $viewer->id,
            'attendee_id' => $friend->id,
        ]);

        PointsLog::factory()->create([
            'user_id' => $friend->id,
            'points' => 40,
            'awarded_at' => Carbon::now()->subHours(2),
        ]);

        PointsLog::factory()->create([
            'user_id' => $viewer->id,
            'points' => 10,
            'awarded_at' => Carbon::now()->subHours(1),
        ]);

        // Stranger has points but should be filtered out by connected_only.
        PointsLog::factory()->create([
            'user_id' => $stranger->id,
            'points' => 100,
            'awarded_at' => Carbon::now()->subHours(3),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/leaderboard?period=24h&connected_only=true');

        $response->assertOk()
            ->assertJsonPath('period', '24h')
            ->assertJsonCount(2, 'leaders')
            ->assertJsonPath('leaders.0.name', 'Friend')
            ->assertJsonPath('leaders.0.points', 40)
            ->assertJsonPath('leaders.0.connected', true)
            ->assertJsonPath('leaders.1.name', 'Viewer')
            ->assertJsonPath('leaders.1.points', 10)
            ->assertJsonPath('leaders.1.connected', false)
            ->assertJsonPath('viewer.name', 'Viewer')
            ->assertJsonPath('viewer.rank', 3)
            ->assertJsonPath('viewer.points', 10);
    }

    public function test_leaderboard_filters_by_period(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer']);
        $viewer->profile()->create([
            'job_title' => 'Viewer Role',
            'company_name' => 'Viewer Co',
        ]);

        $recent = User::factory()->create(['name' => 'Recent']);
        $recent->profile()->create([
            'job_title' => 'Recent Role',
            'company_name' => 'Recent Co',
        ]);

        $old = User::factory()->create(['name' => 'Old']);
        $old->profile()->create([
            'job_title' => 'Old Role',
            'company_name' => 'Old Co',
        ]);

        PointsLog::factory()->create([
            'user_id' => $recent->id,
            'points' => 50,
            'awarded_at' => Carbon::now()->subDays(2),
        ]);

        PointsLog::factory()->create([
            'user_id' => $old->id,
            'points' => 200,
            'awarded_at' => Carbon::now()->subDays(60),
        ]);

        PointsLog::factory()->create([
            'user_id' => $viewer->id,
            'points' => 10,
            'awarded_at' => Carbon::now()->subDays(1),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/leaderboard?period=7d');

        $response->assertOk()
            ->assertJsonCount(2, 'leaders')
            ->assertJsonPath('leaders.0.name', 'Recent')
            ->assertJsonPath('leaders.0.points', 50)
            ->assertJsonPath('leaders.1.name', 'Viewer')
            ->assertJsonPath('leaders.1.points', 10)
            ->assertJsonPath('viewer.name', 'Viewer')
            ->assertJsonPath('viewer.rank', 2)
            ->assertJsonPath('viewer.points', 10);
    }

    public function test_leaderboard_viewer_block_omitted_when_zero_points(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer']);
        $viewer->profile()->create([
            'job_title' => 'Viewer Role',
            'company_name' => 'Viewer Co',
        ]);

        $leader = User::factory()->create(['name' => 'Leader']);
        $leader->profile()->create([
            'job_title' => 'Leader Role',
            'company_name' => 'Leader Co',
        ]);

        PointsLog::factory()->create(['user_id' => $leader->id, 'points' => 25]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/leaderboard');

        $response->assertOk()
            ->assertJsonCount(1, 'leaders')
            ->assertJsonPath('viewer', null);
    }
}
