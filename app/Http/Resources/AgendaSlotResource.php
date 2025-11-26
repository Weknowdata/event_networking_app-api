<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AgendaSlot */
class AgendaSlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'speakers' => AgendaSpeakerResource::collection($this->whenLoaded('speakers')),
        ];
    }
}
