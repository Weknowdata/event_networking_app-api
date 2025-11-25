<?php

namespace Database\Seeders;

use App\Models\PointsLog;
use App\Models\PointsSource;
use App\Models\User;
use App\Models\UserConnection;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds demo data for the leaderboard: ~25 users, profiles, ~15 connections, and points.
 */
class LeaderboardDemoSeeder extends Seeder
{
    public function __construct(private readonly Generator $faker)
    {
    }

    public function run(): void
    {
        $users = User::factory()
            ->count(25) // Create a realistic pool of attendees.
            ->create()
            ->each(function (User $user) {
                $this->createProfile($user);
            });

        // Build a light graph of connections for demo/leaderboard purposes.
        $connections = $this->seedConnections($users, 15);

        $this->seedPoints($connections);
    }

    private function createProfile(User $user): void
    {
        $user->profile()->create([
            'job_title' => $this->faker->jobTitle(),
            'company_name' => $this->faker->company(),
            'avatar_url' => null,
            'linkedin_url' => $this->faker->url(),
            'location' => $this->faker->city(),
            'bio' => $this->faker->sentence(8),
            'phone_number' => $this->faker->phoneNumber(),
            'is_first_timer' => $this->faker->boolean(30),
            'tags' => [$this->faker->word(), $this->faker->word()],
        ]);
    }

    private function seedConnections(Collection $users, int $target): Collection
    {
        $tokens = [];
        $connections = collect();
        $attempts = 0;

        while ($connections->count() < $target && $attempts < $target * 5) {
            $attempts++;
            /** @var User $a */
            /** @var User $b */
            [$a, $b] = $users->random(2)->all();

            if ($a->id === $b->id) {
                continue;
            }

            $token = $this->pairToken($a->id, $b->id);

            if (isset($tokens[$token])) {
                continue;
            }

            $tokens[$token] = true;

            $isFirstTimer = (bool) ($b->profile?->is_first_timer ?? false);
            $connection = UserConnection::create([
                'user_id' => $a->id,
                'attendee_id' => $b->id,
                'pair_token' => $token,
                'is_first_timer' => $isFirstTimer,
                'user_notes_added' => false,
                'user_notes' => null,
                'attendee_notes_added' => false,
                'attendee_notes' => null,
                'connected_at' => Carbon::now()->subDays(random_int(0, 60)),
            ]);

            $connections->push($connection);
        }

        return $connections;
    }

    private function seedPoints(Collection $connections): void
    {
        foreach ($connections as $connection) {
            $base = $connection->is_first_timer ? UserConnection::FIRST_TIMER_POINTS : UserConnection::RETURNING_POINTS;

            PointsLog::create([
                'user_id' => $connection->user_id,
                'user_connection_id' => $connection->id,
                'source_type' => PointsSource::CONNECTION->value,
                'points' => $base,
                'metadata' => ['role' => 'initiator', 'seeded' => true],
                'awarded_at' => Carbon::now()->subDays(random_int(0, 60)),
            ]);

            PointsLog::create([
                'user_id' => $connection->attendee_id,
                'user_connection_id' => $connection->id,
                'source_type' => PointsSource::CONNECTION->value,
                'points' => $base,
                'metadata' => ['role' => 'attendee', 'seeded' => true],
                'awarded_at' => Carbon::now()->subDays(random_int(0, 60)),
            ]);

            // Bonus note points for about half of the connections on a random side.
            if ($this->faker->boolean(50)) {
                $targetUserId = $this->faker->boolean(60) ? $connection->user_id : $connection->attendee_id;

                PointsLog::create([
                    'user_id' => $targetUserId,
                    'user_connection_id' => $connection->id,
                    'source_type' => PointsSource::CONNECTION_NOTE->value,
                    'points' => $base,
                    'metadata' => [
                        'role' => $targetUserId === $connection->user_id ? 'initiator' : 'attendee',
                        'seeded' => true,
                    ],
                    'awarded_at' => Carbon::now()->subDays(random_int(0, 60)),
                ]);
            }
        }
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }
}
