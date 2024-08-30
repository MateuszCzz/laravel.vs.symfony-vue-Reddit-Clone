<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
class ResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PROTECTED_ROUTE = '/api/auth/logout';
    private const SUCCESSFUL_STATUS_PROTECTED = 205;
    private const NON_PROTECTED_ROUTE = '/api/auth/generate-nickname';
    private const SUCCESSFUL_STATUS_NON_PROTECTED = 200;
    private const AUTH_ERROR_MESSAGE = 'Unauthenticated.';
    private const AUTH_ERROR_MESSAGE_MISSING_TOKEN = 'Unauthenticated - The Token is required.';
    private const AUTH_ERROR_MESSAGE_EXPIRED_TOKEN = 'Unauthenticated - The token is expired.';
    private const AUTH_ERROR_MESSAGE_INVALID_TOKEN = 'Unauthenticated - The token is invalid.';
    private const FAILED_AUTHENTICATION_STATUS = 401;

    /**
     * Make a POST request with a token to a specific route.
     */
    private function tokenProtectedPost(string $token, string $route = self::PROTECTED_ROUTE): TestResponse
    {
        return $this->postJson($route, [], [
            'Authorization' => "Bearer $token",
        ]);
    }

    /**
     * Generate a user and an access token.
     */
    private function generateUserWithToken(bool $expired = false): array
    {
        $user = User::factory()->create();
        $expiration = $expired ? now()->subHour() : now()->addHour();

        return [
            'user' => $user,
            'token' => $user->createToken('access-token', ['*'], $expiration)->plainTextToken,
        ];
    }

    #[Test]
    public function user_can_access_protected_resource_with_valid_token(): void
    {
        $data = $this->generateUserWithToken();
        $response = $this->tokenProtectedPost($data['token']);

        $response->assertStatus(self::SUCCESSFUL_STATUS_PROTECTED);
    }

    #[Test]
    public function user_cannot_access_protected_resource_with_expired_token(): void
    {
        $data = $this->generateUserWithToken(true);
        $response = $this->tokenProtectedPost($data['token']);

        $response->assertJson(['message' => self::AUTH_ERROR_MESSAGE_EXPIRED_TOKEN])
            ->assertStatus(self::FAILED_AUTHENTICATION_STATUS);
    }

    #[Test]
    public function user_cannot_access_protected_resource_with_invalid_token(): void
    {
        $response = $this->tokenProtectedPost('invalid_token');

        $response->assertJson(['message' => self::AUTH_ERROR_MESSAGE_INVALID_TOKEN])
            ->assertStatus(self::FAILED_AUTHENTICATION_STATUS);
    }

    #[Test]
    public function user_cannot_access_protected_resource_without_token(): void
    {
        $response = $this->tokenProtectedPost('');

        $response->assertJson(['message' => self::AUTH_ERROR_MESSAGE_MISSING_TOKEN])
            ->assertStatus(self::FAILED_AUTHENTICATION_STATUS);
    }

    #[Test]
    public function user_can_access_non_protected_resource_with_valid_token(): void
    {
        $data = $this->generateUserWithToken();
        $response = $this->tokenProtectedPost($data['token'], self::NON_PROTECTED_ROUTE);

        $response->assertStatus(self::SUCCESSFUL_STATUS_NON_PROTECTED);
    }

    #[Test]
    public function user_can_access_non_protected_resource_without_token(): void
    {
        $response = $this->tokenProtectedPost('', self::NON_PROTECTED_ROUTE);

        $response->assertStatus(self::SUCCESSFUL_STATUS_NON_PROTECTED);
    }

    #[Test]
    public function user_can_access_non_protected_resource_with_invalid_token(): void
    {
        $response = $this->tokenProtectedPost('invalid_token', self::NON_PROTECTED_ROUTE);

        $response->assertStatus(self::SUCCESSFUL_STATUS_NON_PROTECTED);
    }
}
