<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnectAttendeeRequest;
use App\Http\Requests\UpdateConnectionNotesRequest;
use App\Http\Resources\UserConnectionResource;
use App\Http\Resources\UserResource;
use App\Models\PointsLog;
use App\Models\PointsSource;
use App\Models\User;
use App\Models\UserConnection;
use App\Services\ChallengeEvaluator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    private const DAILY_LIMIT = 15;
    private const NOTE_POINTS = 10;

    public function __construct(private ChallengeEvaluator $challengeEvaluator)
    {
    }

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
            // Shape each connection from the perspective of the viewer (could be either side).
            ->map(function (UserConnection $connection) use ($user) {
                $connection = $this->attachComputedPoints($connection);
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
                    'total_points' => $connection->total_points ?? 0,
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

        // Enforce a per-day connection cap to slow abuse.
        if ($this->hasReachedDailyLimit($user->id)) {
            return $this->errorResponse('Daily connection limit reached. Try again tomorrow.', 429);
        }

        $pairToken = $this->pairToken($user->id, $attendee->id);

        // Deduplicate the pair regardless of initiator order.
        if (UserConnection::where('pair_token', $pairToken)->exists()) {
            return $this->errorResponse('You have already connected with this attendee.');
        }

        $isFirstTimer = (bool) ($attendee->profile?->is_first_timer ?? false);
        $basePoints = $this->basePoints($isFirstTimer);
        $userNotes = $request->notes();
        $userNotesAdded = $userNotes !== null;

        // Create the symmetric connection record with note flags for each participant.
        $connection = UserConnection::create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'pair_token' => $pairToken,
            'is_first_timer' => $isFirstTimer,
            'user_notes_added' => $userNotesAdded,
            'user_notes' => $userNotes,
            'attendee_notes_added' => false,
            'attendee_notes' => null,
            'connected_at' => now(),
        ]);

        // Award base points only to the initiator (scanner).
        $this->logPoints($connection, $user, $basePoints, PointsSource::CONNECTION, ['role' => 'initiator']);

        // Bonus points when the initiator adds notes during creation.
        if ($userNotesAdded) {
            $this->logPoints($connection, $user, self::NOTE_POINTS, PointsSource::CONNECTION_NOTE);
        }

        $this->attachComputedPoints($connection);
        // Trigger daily challenge evaluation so connection-based goals award points.
        $this->challengeEvaluator->evaluateForDay($user, Carbon::parse($connection->connected_at));

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

            // Viewer is the connection initiator.
            $connection->user_notes = $notes;
            $connection->user_notes_added = true;
        } else {
            if ($connection->attendee_notes_added) {
                return $this->errorResponse('Notes were already added for this connection.');
            }

            // Viewer is the attendee being connected to.
            $connection->attendee_notes = $notes;
            $connection->attendee_notes_added = true;
        }

        $connection->save();

        $basePoints = $this->basePoints((bool) $connection->is_first_timer);
        $this->logPoints(
            $connection,
            $user,
            self::NOTE_POINTS,
            PointsSource::CONNECTION_NOTE
        );

        $this->attachComputedPoints($connection);

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

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }

    private function basePoints(bool $isFirstTimer): int
    {
        return $isFirstTimer
            ? UserConnection::FIRST_TIMER_POINTS
            : UserConnection::RETURNING_POINTS;
    }

    private function connectionPointsValue(UserConnection $connection): int
    {
        $base = $this->basePoints((bool) $connection->is_first_timer);
        $bonus = 0;

        // Each side earns a flat bonus for adding notes.
        if ($connection->user_notes_added) {
            $bonus += self::NOTE_POINTS;
        }

        if ($connection->attendee_notes_added) {
            $bonus += self::NOTE_POINTS;
        }

        // Base points awarded to initiator only; total_points reflects aggregate per-connection.
        return $base + $bonus;
    }

    private function attachComputedPoints(UserConnection $connection): UserConnection
    {
        $connection->setAttribute('total_points', $this->connectionPointsValue($connection));

        return $connection;
    }

    private function logPoints(UserConnection $connection, User $user, int $points, PointsSource $source, array $metadata = []): void
    {
        PointsLog::create([
            'user_id' => $user->id,
            'user_connection_id' => $connection->id,
            'source_type' => $source->value,
            'points' => $points,
            'metadata' => $metadata,
            'awarded_at' => now(),
        ]);
    }
}
