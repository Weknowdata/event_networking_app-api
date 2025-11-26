<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_agenda(): void
    {
        $this->getJson('/api/agenda')->assertStatus(401);
    }

    public function test_can_generate_five_day_agenda_with_hourly_slots(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/agenda/generate', [
            'start_date' => '2024-10-01',
            'days_count' => 5,
        ]);

        $response->assertCreated();

        $agenda = $response->json('agenda');
        $this->assertCount(5, $agenda);

        $firstDay = $agenda[0];
        $this->assertEquals(1, $firstDay['day_number']);
        $this->assertEquals('2024-10-01', $firstDay['date']);
        $this->assertCount(8, $firstDay['slots']); // 9am-5pm hourly slots.
        $this->assertEquals('09:00', $firstDay['slots'][0]['start_time']);
        $this->assertEquals('10:00', $firstDay['slots'][0]['end_time']);
        $this->assertEquals('16:00', $firstDay['slots'][7]['start_time']);
        $this->assertEquals('17:00', $firstDay['slots'][7]['end_time']);
        // Speakers should be present in the payload, even if empty for generated agendas.
        $this->assertIsArray($firstDay['slots'][0]['speakers']);
    }

    public function test_can_generate_seven_day_agenda(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/agenda/generate', [
            'start_date' => '2024-11-15',
            'days_count' => 7,
        ]);

        $response->assertCreated()
            ->assertJsonCount(7, 'agenda');
    }
}
