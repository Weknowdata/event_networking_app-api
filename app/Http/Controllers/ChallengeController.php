<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChallengeResource;
use App\Models\Challenge;
use App\Models\ChallengeCompletion;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = CarbonImmutable::today();
        $todayNumber = (int) $today->day;
        $progressService = app('challenge.progress');

        $completions = ChallengeCompletion::query()
            ->where('user_id', $user->id)
            ->whereDate('event_day', '=', $today)
            ->get()
            ->keyBy('challenge_id');

        $challenges = Challenge::query()
            ->where('is_enabled', true)
            ->where(function ($query) use ($today) {
                $query->whereNull('active_start')->orWhereDate('active_start', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('active_end')->orWhereDate('active_end', '>=', $today);
            })
            ->get()
            ->map(function ($challenge) use ($completions, $todayNumber, $progressService, $user) {
                $completion = $completions->get($challenge->id);
                $counts = $progressService->countsForToday($user);
                $progress = $progressService->progressFor($challenge, $counts);

                $status = 'in_progress';
                $awarded = false;
                $completedAt = null;

                if ($completion) {
                    $status = 'completed';
                    $awarded = true;
                    $completedAt = $completion->completed_at;
                } elseif (! $progressService->isActiveForDay($challenge, $todayNumber)) {
                    $status = 'locked';
                }

                $challenge->setAttribute('progress', $progress);
                $challenge->setAttribute('status', $status);
                $challenge->setAttribute('awarded', $awarded);
                $challenge->setAttribute('completed_at', $completedAt);

                return $challenge;
            });

        return response()->json([
            'date' => $today->toDateString(),
            'challenges' => ChallengeResource::collection($challenges),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = ChallengeCompletion::query()
            ->with('challenge')
            ->where('user_id', $user->id)
            ->orderByDesc('event_day')
            ->paginate(20);

        $data = $history->getCollection()->map(function ($completion) {
            return [
                'id' => $completion->id,
                'challenge_id' => $completion->challenge_id,
                'code' => $completion->challenge?->code,
                'title' => $completion->challenge?->title,
                'points' => $completion->awarded_points,
                'event_day' => $completion->event_day?->toDateString(),
                'completed_at' => $completion->completed_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}
