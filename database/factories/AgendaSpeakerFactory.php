<?php

namespace Database\Factories;

use App\Models\AgendaSlot;
use App\Models\AgendaSpeaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaSpeaker>
 */
class AgendaSpeakerFactory extends Factory
{
    protected $model = AgendaSpeaker::class;

    public function definition(): array
    {
        return [
            'agenda_slot_id' => AgendaSlot::factory(),
            'name' => $this->faker->name(),
            'title' => $this->faker->jobTitle(),
            'company' => $this->faker->company(),
            'bio' => $this->faker->optional()->sentence(10),
            'avatar_url' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'sort_order' => 0,
            'user_id' => null,
        ];
    }
}
