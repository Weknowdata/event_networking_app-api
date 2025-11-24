<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleMobileAuthRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    public function mobile(GoogleMobileAuthRequest $request): JsonResponse
    {
        $payload = $this->verifyIdToken($request->string('id_token'));

        [$user, $wasNew] = DB::transaction(function () use ($payload) {
            $user = $this->resolveUserFromGooglePayload($payload);

            return [$user->load('profile'), $user->wasRecentlyCreated];
        });

        $token = $user->createToken('google-mobile')->plainTextToken;

        return response()->json([
            'message' => $wasNew ? 'Account created via Google.' : 'Login successful via Google.',
            'token' => $token,
            'user' => $user,
        ], $wasNew ? 201 : 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyIdToken(string $idToken): array
    {
        try {
            $response = Http::retry(2, 250)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ])->throw();
        } catch (ConnectionException|RequestException $exception) {
            $this->throwInvalidToken();
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            $this->throwInvalidToken();
        }

        $allowedAudiences = (array) config('services.google.mobile_client_ids', []);
        $audience = Arr::get($payload, 'aud');

        if (! empty($allowedAudiences) && ! in_array($audience, $allowedAudiences, true)) {
            $this->throwInvalidToken();
        }

        $issuer = Arr::get($payload, 'iss');
        if (! in_array($issuer, ['https://accounts.google.com', 'accounts.google.com'], true)) {
            $this->throwInvalidToken();
        }

        $expiresAt = Arr::get($payload, 'exp');
        if ($expiresAt && Carbon::createFromTimestamp((int) $expiresAt)->isPast()) {
            $this->throwInvalidToken();
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveUserFromGooglePayload(array $payload): User
    {
        $email = Arr::get($payload, 'email');

        if (! $email) {
            $this->throwInvalidToken();
        }

        $user = User::firstWhere('email', $email);

        if (! $user) {
            return $this->createUserFromPayload($payload);
        }

        $this->syncUserVerificationStatus($user, $payload);
        $this->ensureProfileExists($user, $payload);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createUserFromPayload(array $payload): User
    {
        $email = Arr::get($payload, 'email');
        $firstName = Arr::get($payload, 'given_name', '');
        $lastName = Arr::get($payload, 'family_name', '');
        $name = Arr::get($payload, 'name') ?: User::buildName($firstName, $lastName) ?: (string) $email;

        $user = new User([
            'name' => $name,
            'email' => (string) $email,
            'password' => Str::random(40),
        ]);

        if (filter_var(Arr::get($payload, 'email_verified'), FILTER_VALIDATE_BOOLEAN)) {
            $user->email_verified_at = now();
        }

        $user->save();

        $this->ensureProfileExists($user, $payload);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncUserVerificationStatus(User $user, array $payload): void
    {
        if ($user->email_verified_at) {
            return;
        }

        if (filter_var(Arr::get($payload, 'email_verified'), FILTER_VALIDATE_BOOLEAN)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensureProfileExists(User $user, array $payload): void
    {
        $profile = $user->profile;
        $picture = Arr::get($payload, 'picture');

        if ($profile) {
            if (! $profile->avatar_url && $picture) {
                $profile->avatar_url = $picture;
                $profile->save();
            }

            return;
        }

        $user->profile()->create([
            'job_title' => 'Attendee',
            'company_name' => 'Not provided',
            'avatar_url' => $picture,
            'location' => null,
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => false,
        ]);
    }

    private function throwInvalidToken(): never
    {
        throw ValidationException::withMessages([
            'id_token' => ['The provided Google token is invalid or expired.'],
        ]);
    }
}
