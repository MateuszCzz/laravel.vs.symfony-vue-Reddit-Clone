<?php

namespace Tests\Feature\Auth;

use App\Enum\TokenName;
use Carbon\Carbon;
use Tests\Feature\Traits\AuthHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    private const SUCCESSFUL_LOGIN_STATUS = 200;

    #[Test]
    public function test_user_can_login_with_nickname(): void
    {
        $this->createUser();
        $this->loginUserPost()
            ->assertJsonStructure(self::SUCCESSFUL_AUTH_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_LOGIN_STATUS);
    }

    #[Test]
    public function test_user_can_login_with_email(): void
    {
        $this->createUser();
        $this->loginUserPost(false)
            ->assertJsonStructure(self::SUCCESSFUL_AUTH_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_LOGIN_STATUS);
    }

    #[Test]
    public function test_successful_login_creates_new_token_in_database(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $this->assertEquals(
            1,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Token should exist.'
        );
    }

    #[Test]
    public function test_created_tokens_are_properly_time_limited(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $token = $this->findUserTokens($response->json('user.id'))
            ->first();

        $accessTokenExpiration = config('sanctum.ac_expiration');
        if (is_null($accessTokenExpiration)) {
            $this->assertNull($token->expires_at, 'Token should never expire.');
        } else {
            $expiresAt = Carbon::parse($token->expires_at);
            $this->assertTrue(
                $expiresAt->lessThanOrEqualTo(Carbon::now()->addMinutes($accessTokenExpiration)),
                'The token should expire within configured time.'
            );
        }
    }

    #[Test]
    public function test_user_can_generate_remember_me_token(): void
    {
        $this->createUser();
        $response = $this->loginUserPost(rememberMe: true)
            ->assertJsonStructure(self::SUCCESSFUL_AUTH_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_LOGIN_STATUS);

        $this->assertEquals(
            1,
            $this->findUserTokens(
                $response->json('user.id'),
                TokenName::REMEMBER_ME_ACCESS_TOKEN->value
            )->count(),
            'Token should exist.'
        );
    }

    #[Test]
    public function test_user_cannot_login_with_unregistered_credentials(): void
    {
        $this->loginUserPost(false, email: self::USER_EMAIL_DEFAULT . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'login' => 'The provided credentials are incorrect.',
                'password' => 'The provided credentials are incorrect.'
            ]);

        $this->loginUserPost(nickname: self::USER_NICKNAME_DEFAULT . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'login' => 'The provided credentials are incorrect.',
                'password' => 'The provided credentials are incorrect.'
            ]);
    }

    #[Test]
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $this->createUser();
        $this->loginUserPost(false, email: self::USER_EMAIL_DEFAULT . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'login' => 'The provided credentials are incorrect.',
                'password' => 'The provided credentials are incorrect.'
            ]);

        $this->loginUserPost(nickname: self::USER_NICKNAME_DEFAULT . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'login' => 'The provided credentials are incorrect.',
                'password' => 'The provided credentials are incorrect.'
            ]);
    }

    #[Test]
    public function test_user_cannot_login_without_password(): void
    {
        $this->createUser();
        $this->loginUserPost(password: '')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => 'The password field is required.'
            ]);
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_successful_login_attempt(): void
    // {
    //     $this->assertTrue(true);
    // }
}
