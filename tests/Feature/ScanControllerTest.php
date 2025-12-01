<?php

namespace Tests\Feature;

use App\Models\AgendaDay;
use App\Models\AgendaSlot;
use App\Models\Challenge;
use App\Models\User;
use App\Services\QrTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_session_scan_and_award_challenge(): void
    {
        $user = User::factory()->create();

        $day = AgendaDay::factory()->create([
            'day_number' => 1,
            'date' => CarbonImmutable::parse('2024-10-01'),
        ]);

        $slot = AgendaSlot::factory()->create([
            'agenda_day_id' => $day->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'title' => 'Session 1',
        ]);

        Challenge::create([
            'code' => 'TEST_SESSION',
            'title' => 'Attend 1 session',
            'description' => null,
            'points' => 50,
            'requirements' => ['session_attended' => 1],
            'frequency' => 'daily',
            'applies_to_day' => 1,
            'is_enabled' => true,
            'max_completions_per_user_per_day' => 1,
        ]);

        config()->set('qr.signing_secret', 'testing-secret');
        $qrTokens = new QrTokenService('testing-secret');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/scan/session', [
            'agenda_slot_id' => $slot->id,
            'qr_token' => $qrTokens->mintToken($slot->id),
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('session_attendances', [
            'user_id' => $user->id,
            'agenda_slot_id' => $slot->id,
        ]);

        $this->assertDatabaseHas('challenge_completions', [
            'user_id' => $user->id,
            'challenge_id' => Challenge::where('code', 'TEST_SESSION')->first()->id,
        ]);

        $this->assertDatabaseHas('points_logs', [
            'user_id' => $user->id,
            'source_type' => 'challenge',
            'points' => 50,
        ]);
    }

    public function test_record_booth_scan_counts_distinct_visits(): void
    {
        $user = User::factory()->create();

        Challenge::create([
            'code' => 'TEST_BOOTH',
            'title' => 'Visit 2 booths',
            'description' => null,
            'points' => 75,
            'requirements' => ['sponsor_booth_visited' => 2],
            'frequency' => 'daily',
            'is_enabled' => true,
            'max_completions_per_user_per_day' => 1,
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/scan/booth', [
            'booth_code' => 'A1',
            'event_day' => '2024-10-01',
        ])->assertCreated();

        $this->actingAs($user, 'sanctum')->postJson('/api/scan/booth', [
            'booth_code' => 'B2',
            'event_day' => '2024-10-01',
        ])->assertCreated();

        $this->assertDatabaseCount('booth_visits', 2);

        $this->assertDatabaseHas('challenge_completions', [
            'user_id' => $user->id,
            'challenge_id' => Challenge::where('code', 'TEST_BOOTH')->first()->id,
        ]);
    }
}
