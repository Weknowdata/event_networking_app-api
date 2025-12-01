<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ChallengeProgress
{
    /**
     * @return array<string, int>
     */
    public function countsForToday(User $user): array
    {
        $today = CarbonImmutable::today();

        $connectionCounts = DB::table('user_connections')
            ->selectRaw('count(*) as total, sum(case when is_first_timer = 1 then 1 else 0 end) as first_timers')
            ->where('user_id', $user->id)
            ->whereDate('connected_at', '=', $today)
            ->first();

        $sessionCount = DB::table('session_attendances')
            ->where('user_id', $user->id)
            ->whereDate('event_day', '=', $today)
            ->where('valid_for_points', true)
            ->count();

        $boothCount = DB::table('booth_visits')
            ->where('user_id', $user->id)
            ->whereDate('event_day', '=', $today)
            ->distinct('booth_code')
            ->count('booth_code');

        return [
            'connection_made' => (int) ($connectionCounts->total ?? 0),
            'met_first_timer' => (int) ($connectionCounts->first_timers ?? 0),
            'session_attended' => (int) $sessionCount,
            'sponsor_booth_visited' => (int) $boothCount,
        ];
    }

    /**
     * @param array<string, int> $counts
     * @return array{current:int,target:int}|null
     */
    public function progressFor(Challenge $challenge, array $counts): ?array
    {
        $requirements = $challenge->requirements ?? [];

        if (isset($requirements['any']) && is_array($requirements['any']) && count($requirements['any']) > 0) {
            // For "any" sets, show the best-progress option.
            $best = null;
            foreach ($requirements['any'] as $option) {
                [$current, $target] = $this->progressFromRequirements($option, $counts);
                if ($best === null || ($target - $current) < ($best['target'] - $best['current'])) {
                    $best = ['current' => $current, 'target' => $target];
                }
            }

            return $best;
        }

        [$current, $target] = $this->progressFromRequirements($requirements, $counts);

        return ['current' => $current, 'target' => $target];
    }

    public function isActiveForDay(Challenge $challenge, int $dayNumber): bool
    {
        return $challenge->applies_to_day === null || $challenge->applies_to_day === $dayNumber;
    }

    /**
     * @param array<string, mixed> $requirements
     * @param array<string, int> $counts
     * @return array{int,int}
     */
    private function progressFromRequirements(array $requirements, array $counts): array
    {
        $current = 0;
        $target = 0;

        foreach ($requirements as $action => $threshold) {
            if (! is_numeric($threshold)) {
                continue;
            }

            $target += (int) $threshold;
            $current += min($counts[$action] ?? 0, (int) $threshold);
        }

        return [$current, max(1, $target)];
    }
}
