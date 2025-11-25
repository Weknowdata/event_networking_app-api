<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserConnection;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds a set of unique user connections for demo data.
 */
class ConnectionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::with('profile')->get();

        if ($users->count() < 2) {
            return;
        }

        $this->seedConnections($users, 15);
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

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }
}
