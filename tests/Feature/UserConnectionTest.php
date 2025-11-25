<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_connection_with_first_timer_and_notes(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $attendee->profile()->create([
            'job_title' => 'Engineer',
            'company_name' => 'Tech Corp',
            'location' => 'Remote',
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'notes' => 'Great chat about product ideas.',
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('connection.total_points', 60)
            ->assertJsonPath('connection.notes_added', true)
            ->assertJsonPath('connection.user_notes_added', true)
            ->assertJsonPath('connection.attendee_notes_added', false);

        $this->assertDatabaseHas('user_connections', [
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'user_notes_added' => true,
            'attendee_notes_added' => false,
        ]);
    }

    public function test_user_can_create_connection_with_returning_attendee_without_notes(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $attendee->profile()->create([
            'job_title' => 'PM',
            'company_name' => 'API Hub',
            'location' => null,
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('connection.total_points', 25)
            ->assertJsonPath('connection.notes_added', false)
            ->assertJsonPath('connection.user_notes_added', false)
            ->assertJsonPath('connection.attendee_notes_added', false);
    }

    public function test_daily_cap_prevents_more_than_fifteen_connections(): void
    {
        $user = User::factory()->create();

        User::factory()->count(15)->create()->each(function (User $attendee) use ($user) {
            UserConnection::factory()->create([
                'user_id' => $user->id,
                'attendee_id' => $attendee->id,
                'pair_token' => $this->pairToken($user->id, $attendee->id),
                'connected_at' => now(),
            ]);
        });

        $extra = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $extra->id,
            'signature' => $extra->qrSignature(),
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Daily connection limit reached. Try again tomorrow.');
    }

    public function test_user_cannot_connect_with_same_attendee_twice(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'pair_token' => $this->pairToken($user->id, $attendee->id),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'You have already connected with this attendee.');
    }

    public function test_user_cannot_connect_if_other_party_already_connected(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        UserConnection::factory()->create([
            'user_id' => $attendee->id,
            'attendee_id' => $user->id,
            'pair_token' => $this->pairToken($user->id, $attendee->id),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'You have already connected with this attendee.');
    }

    public function test_invalid_signature_rejected(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The scan cannot be verified. Please try again.');
    }

    public function test_notes_can_be_submitted_later_and_double_points(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'is_first_timer' => false,
            'user_notes_added' => false,
            'user_notes' => null,
            'attendee_notes_added' => false,
            'attendee_notes' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Sent follow up email.',
        ]);

        $response->assertOk()
            ->assertJsonPath('connection.total_points', 35)
            ->assertJsonPath('connection.notes_added', true)
            ->assertJsonPath('connection.user_notes_added', true)
            ->assertJsonPath('connection.attendee_notes_added', false);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'user_notes_added' => true,
            'attendee_notes_added' => false,
        ]);
    }

    public function test_attendee_can_add_notes_and_receive_points_bonus(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'is_first_timer' => true,
            'user_notes_added' => false,
            'attendee_notes_added' => false,
        ]);

        $response = $this->actingAs($attendee, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Following up next week.',
        ]);

        $response->assertOk()
            ->assertJsonPath('connection.total_points', 60)
            ->assertJsonPath('connection.notes_added', true)
            ->assertJsonPath('connection.attendee_notes_added', true)
            ->assertJsonPath('connection.user_notes_added', false);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'attendee_notes_added' => true,
        ]);
    }

    public function test_total_points_increase_when_both_participants_add_notes(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'is_first_timer' => false,
            'user_notes_added' => false,
            'attendee_notes_added' => false,
        ]);

        $this->actingAs($user, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Initial note.',
        ])->assertOk()
            ->assertJsonPath('connection.total_points', 35);

        $response = $this->actingAs($attendee, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Second perspective.',
        ]);

        $response->assertOk()
            ->assertJsonPath('connection.total_points', 45)
            ->assertJsonPath('connection.user_notes_added', true)
            ->assertJsonPath('connection.attendee_notes_added', true);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'user_notes_added' => true,
            'attendee_notes_added' => true,
        ]);
    }

    public function test_attendee_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => '999',
            'signature' => hash_hmac('sha256', '999', config('app.key')),
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Attendee not found.');
    }

    public function test_user_cannot_update_someone_elses_connection_notes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $other->id,
            'attendee_id' => $attendee->id,
            'user_notes_added' => false,
            'attendee_notes_added' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/connections/{$connection->id}/notes", [
                'notes' => 'Trying to update.',
            ])
            ->assertStatus(403);
    }

    public function test_notes_endpoint_blocks_duplicate_submission(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'user_notes_added' => true,
            'attendee_notes_added' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Second attempt.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Notes were already added for this connection.');
    }

    public function test_attendee_cannot_submit_notes_twice(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'user_notes_added' => false,
            'attendee_notes_added' => true,
        ]);

        $this->actingAs($attendee, 'sanctum')
            ->patchJson("/api/connections/{$connection->id}/notes", [
                'notes' => 'Duplicate attempt.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Notes were already added for this connection.');
    }

    public function test_user_can_list_all_their_connections(): void
    {
        $user = User::factory()->create();
        $outboundAttendee = User::factory()->create();
        $inboundAttendee = User::factory()->create();

        $outboundAttendee->profile()->create([
            'job_title' => 'Designer',
            'company_name' => 'Studio',
            'avatar_url' => null,
            'linkedin_url' => null,
            'location' => null,
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => true,
            'tags' => [],
        ]);

        $inboundAttendee->profile()->create([
            'job_title' => 'Engineer',
            'company_name' => 'Build Co',
            'avatar_url' => null,
            'linkedin_url' => null,
            'location' => null,
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => false,
            'tags' => [],
        ]);

        $earlierConnection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $outboundAttendee->id,
            'connected_at' => now()->subDay(),
            'is_first_timer' => true,
            'user_notes' => 'Notes here',
            'user_notes_added' => true,
            'attendee_notes' => null,
            'attendee_notes_added' => false,
        ]);

        $latestConnection = UserConnection::factory()->create([
            'user_id' => $inboundAttendee->id,
            'attendee_id' => $user->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/connections');

        $response->assertOk()
            ->assertJsonCount(2, 'connections')
            ->assertJsonPath('connections.0.connection_id', $latestConnection->id)
            ->assertJsonPath('connections.0.attendee_id', $inboundAttendee->id)
            ->assertJsonPath('connections.0.attendee.id', $inboundAttendee->id)
            ->assertJsonPath('connections.1.connection_id', $earlierConnection->id)
            ->assertJsonPath('connections.1.attendee_id', $outboundAttendee->id)
            ->assertJsonPath('connections.1.attendee.id', $outboundAttendee->id)
            ->assertJsonPath('connections.1.notes', 'Notes here')
            ->assertJsonPath('connections.1.other_notes', null);
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }
}
