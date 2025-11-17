<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $user?->loadMissing('profile');

        return response()->json([
            'message' => 'Profile fetched successfully.',
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();
        $profileData = Arr::except($data, ['name']);

        /** @var UserProfile|null $profile */
        $profile = $user->profile;

        if ($profile === null) {
            $profile = new UserProfile();
            $profile->user()->associate($user);
        }

        $this->authorize('update', $profile);

        if (array_key_exists('name', $data)) {
            $user->forceFill(['name' => $data['name']])->save();
        }

        if (! empty($profileData)) {
            $profile->fill($profileData);
            $profile->save();
        }

        $user->load('profile');

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }
}
