<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserConnection */
class UserConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        $viewerIsUser = $viewerId !== null && $viewerId === $this->user_id;
        $viewerIsAttendee = $viewerId !== null && $viewerId === $this->attendee_id;
        $notesAdded = $viewerIsUser
            ? $this->user_notes_added
            : ($viewerIsAttendee ? $this->attendee_notes_added : false);
        $notes = $viewerIsUser
            ? $this->user_notes
            : ($viewerIsAttendee ? $this->attendee_notes : null);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attendee_id' => $this->attendee_id,
            'is_first_timer' => $this->is_first_timer,
            'base_points' => $this->base_points,
            'total_points' => $this->total_points,
            'notes_added' => $notesAdded,
            'notes' => $notes,
            'user_notes_added' => $this->user_notes_added,
            'user_notes' => $this->user_notes,
            'attendee_notes_added' => $this->attendee_notes_added,
            'attendee_notes' => $this->attendee_notes,
            'connected_at' => $this->connected_at?->toIso8601String(),
        ];
    }
}
