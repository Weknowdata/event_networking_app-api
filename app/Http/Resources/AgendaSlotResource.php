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
        $data = [
            'id' => $this->id,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'type' => $this->type,
            'speakers' => AgendaSpeakerResource::collection($this->whenLoaded('speakers')),
            'checked_in' => (bool) $this->getAttribute('checked_in'),
        ];

        $qrAllowed = ! config('features.workshop_only_checkins', false) || $this->type === 'workshop';

        if ($qrAllowed) {
            $ttlSeconds = max(60, (int) config('qr.slot_token_ttl_seconds', 900));
            $token = app(\App\Services\QrTokenService::class)->mintToken($this->id, $ttlSeconds);

            $data['qr_token'] = $token;
            $data['qr_exp'] = now()->addSeconds($ttlSeconds)->getTimestampMs();
        }

        return $data;
    }
}
