<?php

namespace Tests\Feature\Auth;

use Tests\Feature\Traits\AuthHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
class ResourceAccessTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    // Test Data
    private const TEST_TOKEN = 'token';
    
    // Routes
    private const CHECK_NICKNAME_ROUTE = '/api/auth/check-nickname/';

    // Validation messages
    private const AUTH_ERROR_MESSAGE = 'Unauthenticated.';
    private const AUTH_ERROR_MESSAGE_EXPIRED_TOKEN = 'Unauthenticated - The token expired.';

    // JSON structures
    private const NICKNAME_CHECK_JSON_STRUCTURE = [
        'available',
        'nickname',
    ];

    // Status codes
    private const SUCCESSFUL_NICKNAME_CHECK_STATUS = 200;
    private const FAILED_TOKEN_AUTH_STATUS = 401;

    #[Test]
    public function user_can_access_protected_resource_with_valid_token(): void
    {
        $token = $this->createAccessToken();

        $this->logoutUserPost($token)
            ->assertStatus(self::SUCCESSFUL_LOGOUT_STATUS);
    }

    #[Test]
    public function user_cannot_access_protected_resource_with_expired_token(): void
    {
        $token = $this->createAccessToken(isExpired: true);

        $this->logoutUserPost($token)
            ->assertJson(['message' => self::AUTH_ERROR_MESSAGE_EXPIRED_TOKEN])
            ->assertStatus(self::FAILED_TOKEN_AUTH_STATUS);
    }

    #[Test]
    public function user_cannot_access_protected_resource_with_invalid_token(): void
    {
        $this->logoutUserPost(token: 'error')
            ->assertJson(['message' => self::AUTH_ERROR_MESSAGE])
            ->assertStatus(self::FAILED_TOKEN_AUTH_STATUS);
    }

    #[Test]
    public function user_cannot_access_protected_resource_without_token(): void
    {
        $this->logoutUserPost(token: '')
            ->assertJson(['message' => self::AUTH_ERROR_MESSAGE])
            ->assertStatus(self::FAILED_TOKEN_AUTH_STATUS);
    }

    #[Test]
    public function user_can_access_non_protected_resource_with_valid_token(): void
    {
        $token = $this->createAccessToken();
        $this->getJson(
            self::CHECK_NICKNAME_ROUTE . self::USER_NICKNAME_DEFAULT,
            [
                'Authorization' => "Bearer $token"
            ]
        )
            ->assertJsonStructure(self::NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function user_can_access_non_protected_resource_without_token(): void
    {
        $this->getJson(
            self::CHECK_NICKNAME_ROUTE . self::USER_NICKNAME_DEFAULT,
        )
            ->assertJsonStructure(self::NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function user_can_access_non_protected_resource_with_invalid_token(): void
    {
        $this->getJson(
            self::CHECK_NICKNAME_ROUTE . self::USER_NICKNAME_DEFAULT,
            [
                'Authorization' => "Bearer" . self::TEST_TOKEN
            ]
        )
            ->assertJsonStructure(self::NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function user_cannot_access_protected_resource_with_wrong_ability_token(): void
    {
        $token = $this->createAccessToken();

        $this->postJson(
            self::REFRESH_TOKEN_ROUTE,
            [],
            ['Authorization' => "Bearer $token",]
        )
            ->assertJson(['message' => self::AUTH_ERROR_MESSAGE])
            ->assertStatus(self::FAILED_TOKEN_AUTH_STATUS);
    }
}
