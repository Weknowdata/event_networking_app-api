<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{
 *     user: ?\App\Models\User,
 *     rank: int,
 *     points: int,
 *     connected: bool,
 * } $resource
 */
class LeaderboardEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'] ?? null;
        $profile = $user?->profile;

        return [
            'user_id' => $user?->id,
            'name' => $user?->name,
            'company_name' => $profile?->company_name,
            'avatar_url' => $profile?->avatar_url,
            'rank' => $this->resource['rank'] ?? null,
            'points' => $this->resource['points'] ?? 0,
            // Indicates whether the authenticated viewer is connected to this user.
            'connected' => (bool) ($this->resource['connected'] ?? false),
        ];
    }
}
