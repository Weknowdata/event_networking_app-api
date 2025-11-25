<?php

namespace Database\Seeders;

use App\Models\PointsLog;
use App\Models\PointsSource;
use App\Models\UserConnection;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;

/**
 * Seeds points for existing connections to drive leaderboard demos.
 */
class LeaderboardPointsSeeder extends Seeder
{
    public function __construct(private readonly Generator $faker)
    {
    }

    public function run(): void
    {
        $connections = UserConnection::all();

        foreach ($connections as $connection) {
            $base = $connection->is_first_timer
                ? UserConnection::FIRST_TIMER_POINTS
                : UserConnection::RETURNING_POINTS;

            // Base points to initiator (scanner).
            PointsLog::create([
                'user_id' => $connection->user_id,
                'user_connection_id' => $connection->id,
                'source_type' => PointsSource::CONNECTION->value,
                'points' => $base,
                'metadata' => ['role' => 'initiator', 'seeded' => true],
                'awarded_at' => Carbon::now()->subDays(random_int(0, 60)),
            ]);

            // Random note bonuses (flat 10).
            if ($this->faker->boolean(50)) {
                $targetUserId = $this->faker->boolean(60) ? $connection->user_id : $connection->attendee_id;

                PointsLog::create([
                    'user_id' => $targetUserId,
                    'user_connection_id' => $connection->id,
                    'source_type' => PointsSource::CONNECTION_NOTE->value,
                    'points' => 10,
                    'metadata' => [
                        'role' => $targetUserId === $connection->user_id ? 'initiator' : 'attendee',
                        'seeded' => true,
                    ],
                    'awarded_at' => Carbon::now()->subDays(random_int(0, 60)),
                ]);
            }
        }
    }
}
