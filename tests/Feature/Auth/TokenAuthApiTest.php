<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class TokenAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_access_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'api-user@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
            ])
            ->assertJsonFragment([
                'token_type' => 'Bearer',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);

        $this->clearRateLimiter($user->email);
    }

    public function test_login_with_invalid_credentials_returns_validation_error(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-user@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'WrongPassword123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->clearRateLimiter($user->email);
    }

    public function test_logout_revokes_current_access_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout-user@example.com',
        ]);

        $token = $user->createToken('api_token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/logout');

        $response->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    private function clearRateLimiter(string $email): void
    {
        $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');

        RateLimiter::clear($throttleKey);
    }
}
