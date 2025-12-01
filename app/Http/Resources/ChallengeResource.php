<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Challenge */
class ChallengeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $progress = $this->resource['progress'] ?? null;
        $status = $this->resource['status'] ?? 'in_progress';
        $awarded = (bool) ($this->resource['awarded'] ?? false);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'points' => $this->points,
            'frequency' => $this->frequency,
            'applies_to_day' => $this->applies_to_day,
            'status' => $status,
            'progress' => $progress,
            'category' => $this->resource['category'] ?? null,
            'streak' => $this->resource['streak'] ?? null,
            'awarded' => $awarded,
            'completed_at' => $this->resource['completed_at'] ?? null,
        ];
    }
}
