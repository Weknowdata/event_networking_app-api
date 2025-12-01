<?php

namespace App\Services;

use App\Models\BoothVisit;
use App\Models\Challenge;
use App\Models\ChallengeCompletion;
use App\Models\PointsLog;
use App\Models\PointsSource;
use App\Models\SessionAttendance;
use App\Models\User;
use App\Models\UserConnection;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ChallengeEvaluator
{
    /**
     * Evaluate and award all eligible challenges for a user on a given event day.
     */
    public function evaluateForDay(User $user, CarbonInterface $eventDay, ?int $eventDayNumber = null): void
    {
        $counts = $this->actionCounts($user, $eventDay);

        $dayToMatch = $eventDayNumber ?? (int) $eventDay->day;

        $challenges = Challenge::query()
            ->where('is_enabled', true)
            ->where(function ($query) use ($eventDay) {
                $query->whereNull('active_start')->orWhereDate('active_start', '<=', $eventDay);
            })
            ->where(function ($query) use ($eventDay) {
                $query->whereNull('active_end')->orWhereDate('active_end', '>=', $eventDay);
            })
            ->where(function ($query) use ($dayToMatch) {
                $query->whereNull('applies_to_day')->orWhere('applies_to_day', '=', $dayToMatch);
            })
            ->get();

        foreach ($challenges as $challenge) {
            if (! $this->requirementsMet($challenge->requirements ?? [], $counts)) {
                continue;
            }

            $this->awardIfNotAlreadyCompleted($challenge, $user, $eventDay);
        }
    }

    /**
     * @return array<string, int>
     */
    private function actionCounts(User $user, CarbonInterface $eventDay): array
    {
        $connections = UserConnection::query()
            ->where('user_id', $user->id)
            ->whereDate('connected_at', '=', $eventDay)
            ->get();

        $sessionCount = SessionAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('event_day', '=', $eventDay)
            ->where('valid_for_points', true)
            ->count();

        $boothCount = BoothVisit::query()
            ->where('user_id', $user->id)
            ->whereDate('event_day', '=', $eventDay)
            ->distinct('booth_code')
            ->count('booth_code');

        return [
            'connection_made' => $connections->count(),
            'met_first_timer' => $connections->where('is_first_timer', true)->count(),
            'session_attended' => $sessionCount,
            'sponsor_booth_visited' => $boothCount,
        ];
    }

    /**
     * @param array<string, mixed> $requirements
     * @param array<string, int> $counts
     */
    private function requirementsMet(array $requirements, array $counts): bool
    {
        if (isset($requirements['any']) && is_array($requirements['any'])) {
            foreach ($requirements['any'] as $option) {
                if ($this->requirementsMet($option, $counts)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($requirements as $action => $threshold) {
            if (! is_numeric($threshold)) {
                continue;
            }

            $have = $counts[$action] ?? 0;
            if ($have < (int) $threshold) {
                return false;
            }
        }

        return true;
    }

    private function awardIfNotAlreadyCompleted(Challenge $challenge, User $user, CarbonInterface $eventDay): void
    {
        $existing = ChallengeCompletion::query()
            ->where('challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->whereDate('event_day', '=', $eventDay)
            ->first();

        if ($existing) {
            return;
        }

        DB::transaction(function () use ($challenge, $user, $eventDay) {
            $completion = ChallengeCompletion::create([
                'challenge_id' => $challenge->id,
                'user_id' => $user->id,
                'event_day' => $eventDay->toDateString(),
                'awarded_points' => $challenge->points,
                'completed_at' => now(),
                'details' => null,
            ]);

            PointsLog::create([
                'user_id' => $user->id,
                'challenge_completion_id' => $completion->id,
                'source_type' => PointsSource::CHALLENGE->value,
                'points' => $challenge->points,
                'metadata' => ['challenge_code' => $challenge->code],
                'awarded_at' => now(),
            ]);
        });
    }
}
