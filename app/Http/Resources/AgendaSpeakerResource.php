<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AgendaSpeaker */
class AgendaSpeakerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'company' => $this->company,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'sort_order' => $this->sort_order,
            'user_id' => $this->user_id,
        ];
    }
}
