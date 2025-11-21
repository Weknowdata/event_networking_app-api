<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnectAttendeeRequest;
use App\Http\Requests\UpdateConnectionNotesRequest;
use App\Http\Resources\UserConnectionResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserConnection;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    private const DAILY_LIMIT = 15;
    private const FIRST_TIMER_POINTS = 50;
    private const RETURNING_POINTS = 25;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $connections = UserConnection::with(['user.profile', 'attendee.profile'])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('attendee_id', $user->id);
            })
            ->orderByDesc('connected_at')
            ->get()
            ->map(function (UserConnection $connection) use ($user) {
                $viewerIsUser = $connection->user_id === $user->id;
                $other = $viewerIsUser ? $connection->attendee : $connection->user;
                $myNotes = $viewerIsUser ? $connection->user_notes : $connection->attendee_notes;
                $myNotesAdded = $viewerIsUser ? $connection->user_notes_added : $connection->attendee_notes_added;
                $otherNotes = $viewerIsUser ? $connection->attendee_notes : $connection->user_notes;
                $otherNotesAdded = $viewerIsUser ? $connection->attendee_notes_added : $connection->user_notes_added;

                return [
                    'connection_id' => $connection->id,
                    'attendee_id' => $other?->id,
                    'attendee' => $other ? new UserResource($other) : null,
                    'connected_at' => $connection->connected_at?->toIso8601String(),
                    'total_points' => $connection->total_points,
                    'notes_added' => $myNotesAdded,
                    'notes' => $myNotes,
                    'other_notes_added' => $otherNotesAdded,
                    'other_notes' => $otherNotes,
                ];
            })
            ->values();

        return response()->json([
            'connections' => $connections,
        ]);
    }

    public function store(ConnectAttendeeRequest $request): JsonResponse
    {
        $user = $request->user();
        $attendeeId = $request->attendeeId();

        if ($attendeeId === $user->id) {
            return $this->errorResponse('You cannot connect with yourself.');
        }

        $attendee = User::with('profile')->find($attendeeId);

        if (! $attendee) {
            return $this->errorResponse('Attendee not found.', 404);
        }

        $signature = (string) $request->string('signature')->value();

        if (! $this->signatureMatches($attendee, $signature)) {
            return $this->errorResponse('The scan cannot be verified. Please try again.', 422);
        }

        if ($this->hasReachedDailyLimit($user->id)) {
            return $this->errorResponse('Daily connection limit reached. Try again tomorrow.', 429);
        }

        $pairToken = $this->pairToken($user->id, $attendee->id);

        if (UserConnection::where('pair_token', $pairToken)->exists()) {
            return $this->errorResponse('You have already connected with this attendee.');
        }

        $isFirstTimer = (bool) ($attendee->profile?->is_first_timer ?? false);
        $basePoints = $isFirstTimer ? self::FIRST_TIMER_POINTS : self::RETURNING_POINTS;
        $userNotes = $request->notes();
        $userNotesAdded = $userNotes !== null;
        $totalPoints = $this->totalPointsWithNotes(
            $basePoints,
            $userNotesAdded,
            false
        );

        $connection = UserConnection::create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'pair_token' => $pairToken,
            'is_first_timer' => $isFirstTimer,
            'base_points' => $basePoints,
            'total_points' => $totalPoints,
            'user_notes_added' => $userNotesAdded,
            'user_notes' => $userNotes,
            'attendee_notes_added' => false,
            'attendee_notes' => null,
            'connected_at' => now(),
        ]);

        return response()->json([
            'message' => 'Connection recorded.',
            'connection' => new UserConnectionResource($connection),
        ], 201);
    }

    public function updateNotes(UpdateConnectionNotesRequest $request, UserConnection $connection): JsonResponse
    {
        $user = $request->user();

        if ($connection->user_id !== $user->id && $connection->attendee_id !== $user->id) {
            abort(403, 'You are not allowed to update this connection.');
        }

        $notes = $request->validated()['notes'];

        if ($connection->user_id === $user->id) {
            if ($connection->user_notes_added) {
                return $this->errorResponse('Notes were already added for this connection.');
            }

            $connection->user_notes = $notes;
            $connection->user_notes_added = true;
        } else {
            if ($connection->attendee_notes_added) {
                return $this->errorResponse('Notes were already added for this connection.');
            }

            $connection->attendee_notes = $notes;
            $connection->attendee_notes_added = true;
        }

        $connection->total_points = $this->totalPointsWithNotes(
            $connection->base_points,
            (bool) $connection->user_notes_added,
            (bool) $connection->attendee_notes_added
        );
        $connection->save();

        return response()->json([
            'message' => 'Notes saved and points updated.',
            'connection' => new UserConnectionResource($connection),
        ]);
    }

    private function hasReachedDailyLimit(int $userId): bool
    {
        return UserConnection::where('user_id', $userId)
            ->whereDate('connected_at', Carbon::today())
            ->count() >= self::DAILY_LIMIT;
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }

    private function signatureMatches(User $attendee, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        return hash_equals($attendee->qrSignature(), $signature);
    }

    private function totalPointsWithNotes(int $basePoints, bool $userNotesAdded, bool $attendeeNotesAdded): int
    {
        $bonus = 0;

        if ($userNotesAdded) {
            $bonus += $basePoints;
        }

        if ($attendeeNotesAdded) {
            $bonus += $basePoints;
        }

        return $basePoints + $bonus;
    }

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
