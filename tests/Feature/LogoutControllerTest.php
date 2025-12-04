<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Logged out.',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_logout_is_idempotent_when_token_already_deleted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile');

        // Simulate a missing token row; the request will still authenticate via the plain text token.
        PersonalAccessToken::query()->whereKey($token->accessToken->id)->delete();

        $this->withToken($token->plainTextToken)
            ->postJson('/api/logout')
            ->assertUnauthorized();
    }
}
