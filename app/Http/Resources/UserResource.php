<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile' => [
                'job_title' => $profile?->job_title,
                'company_name' => $profile?->company_name,
                'location' => $profile?->location,
                'bio' => $profile?->bio,
                'phone_number' => $profile?->phone_number,
                'is_first_timer' => (bool) ($profile?->is_first_timer ?? false),
                'tags' => $profile?->tags ?? [],
                'avatar_url' => $profile?->avatar_url,
                'linkedin_url' => $profile?->linkedin_url,
            ],
        ];
    }
}
