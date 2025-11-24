<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConnection>
 */
class UserConnectionFactory extends Factory
{
    protected $model = UserConnection::class;

    public function definition(): array
    {
        $isFirstTimer = (bool) $this->faker->boolean;
        $userNotesAdded = $this->faker->boolean;
        $attendeeNotesAdded = $this->faker->boolean;

        return [
            'user_id' => User::factory(),
            'attendee_id' => User::factory(),
            // Populate after making/creating to ensure deterministic ordering of the pair.
            'pair_token' => null,
            'is_first_timer' => $isFirstTimer,
            'user_notes_added' => $userNotesAdded,
            'user_notes' => $userNotesAdded ? $this->faker->sentence : null,
            'attendee_notes_added' => $attendeeNotesAdded,
            'attendee_notes' => $attendeeNotesAdded ? $this->faker->sentence : null,
            'connected_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (UserConnection $connection) {
            $connection->pair_token = $this->pairToken(
                (int) $connection->user_id,
                (int) $connection->attendee_id
            );
        })->afterCreating(function (UserConnection $connection) {
            // Pair token uses sorted IDs to make the relationship symmetric no matter who initiated.
            $connection->updateQuietly([
                'pair_token' => $this->pairToken(
                    (int) $connection->user_id,
                    (int) $connection->attendee_id
                ),
            ]);
        });
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }
}
