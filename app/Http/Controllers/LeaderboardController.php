<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeaderboardEntryResource;
use App\Models\PointsLog;
use App\Models\User;
use App\Models\UserConnection;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LeaderboardController extends Controller
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;

    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $period = $this->normalizePeriod((string) $request->query('period', '30d'));
        $limit = $this->clampLimit((int) $request->integer('limit', self::DEFAULT_LIMIT));
        $periodStart = $this->periodStart($period);
        $connectedOnly = (bool) $request->boolean('connected_only', false);
        $viewerConnections = $viewer
            ? $this->viewerConnectionIds($viewer->id)
            : collect();

        // Aggregate total points per user for the selected window; tie-break by user_id for stability.
        // Optionally constrain to only the viewer’s connections (plus the viewer) when connected_only is set.
        $totals = PointsLog::query()
            ->selectRaw('user_id, SUM(points) as total_points')
            ->when($periodStart, fn ($query) => $query->where('awarded_at', '>=', $periodStart))
            // Optional friends-only/connected-only filter driven by the viewer’s connections.
            ->when(
                $connectedOnly && $viewer,
                fn ($query) => $query->whereIn('user_id', $viewerConnections->concat([$viewer->id])->unique())
            )
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->orderBy('user_id')
            ->limit($limit)
            ->get();

        $userIds = $totals->pluck('user_id')->all();

        if ($viewer) {
            $userIds[] = $viewer->id;
        }

        // Eager load user + profile data for all entries and the viewer.
        $users = User::with('profile')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        // $viewerConnections already computed; reuse to badge rows.

        $leaders = $totals->values()->map(function ($row, int $index) use ($users, $viewerConnections, $viewer) {
            /** @var User|null $user */
            $user = $users->get($row->user_id);

            return [
                'user' => $user,
                'rank' => $index + 1,
                'points' => (int) $row->total_points,
                // Only mark as connected when the viewer is logged in and linked to this user.
                'connected' => $viewer ? $viewerConnections->contains($row->user_id) : false,
            ];
        });

        $viewerEntry = $viewer
            ? $this->viewerStanding($viewer->id, $periodStart, $users->get($viewer->id))
            : null;

        return response()->json([
            'period' => $period,
            'leaders' => LeaderboardEntryResource::collection($leaders),
            'viewer' => $viewerEntry ? new LeaderboardEntryResource($viewerEntry) : null,
        ]);
    }

    private function normalizePeriod(string $period): string
    {
        // Default to 30d to avoid expensive all-time queries unless explicitly requested.
        return in_array($period, ['all_time', '30d', '7d', '24h'], true)
            ? $period
            : '30d';
    }

    private function clampLimit(int $limit): int
    {
        // Avoid unbounded leaderboards; keep payloads modest.
        return max(1, min($limit, self::MAX_LIMIT));
    }

    private function periodStart(string $period): ?Carbon
    {
        return match ($period) {
            '24h' => Carbon::now()->subHours(24),
            '7d' => Carbon::now()->subDays(7),
            'all_time' => null,
            default => Carbon::now()->subDays(30),
        };
    }

    private function viewerConnectionIds(int $viewerId): Collection
    {
        return UserConnection::query()
            ->where(function ($query) use ($viewerId) {
                $query->where('user_id', $viewerId)->orWhere('attendee_id', $viewerId);
            })
            ->get(['user_id', 'attendee_id'])
            ->map(function (UserConnection $connection) use ($viewerId) {
                return $connection->user_id === $viewerId
                    ? $connection->attendee_id
                    : $connection->user_id;
            })
            ->unique()
            ->values();
    }

    private function viewerStanding(int $viewerId, ?Carbon $periodStart, ?User $viewer): ?array
    {
        $viewerPoints = PointsLog::query()
            ->when($periodStart, fn ($query) => $query->where('awarded_at', '>=', $periodStart))
            ->where('user_id', $viewerId)
            ->sum('points');

        if ($viewerPoints === 0) {
            // Avoid returning a viewer block when they have no points for the selected window.
            return null;
        }

        $higherCount = PointsLog::query()
            ->selectRaw('SUM(points) as total_points')
            ->when($periodStart, fn ($query) => $query->where('awarded_at', '>=', $periodStart))
            ->groupBy('user_id')
            ->havingRaw('SUM(points) > ?', [$viewerPoints])
            ->count();

        return [
            'user' => $viewer,
            'rank' => $higherCount + 1,
            'points' => (int) $viewerPoints,
            // Keep shape consistent: viewer block always reflects "connected" as true for self.
            'connected' => true,
        ];
    }
}
