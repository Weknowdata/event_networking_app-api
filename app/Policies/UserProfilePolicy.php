<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserProfile;

class UserProfilePolicy
{
    public function update(User $user, UserProfile $profile): bool
    {
        return $profile->user_id === null || $profile->user_id === $user->id;
    }
}
