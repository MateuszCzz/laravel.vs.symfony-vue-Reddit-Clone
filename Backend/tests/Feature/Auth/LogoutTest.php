<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\AuthHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    private const LOGOUT_ALL_CREDENTIALS_ROUTE = '/api/auth/logout-all-credentials';

    /**
     * Make a POST request to logout given user with credentials.
     *
     * @param bool $logoutWithNickname Whether to login with nickname (true) or email (false).
     * @param string $nickname The nickname of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @return \Illuminate\Testing\TestResponse The response from the logout request.
     */
    private function logoutUserWithCredentialsPost(bool $logoutWithNickname = true, string $nickname = self::USER_NICKNAME_DEFAULT, string $email = self::USER_EMAIL_DEFAULT, string $password = self::USER_PASSWORD_DEFAULT): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(self::LOGOUT_ALL_CREDENTIALS_ROUTE, [
            'login' => $logoutWithNickname ? $nickname : $email,
            'password' => $password,
        ]);
    }

    #[Test]
    public function test_user_can_logout(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $this->logoutUserPost($response->json('access_token'))
            ->assertStatus(self::SUCCESSFUL_LOGOUT_STATUS);

        $this->assertEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Token should not exist.'
        );
    }

    #[Test]
    public function test_user_can_logout_all_tokens_with_password(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        // Create second token
        User::find($response->json('user.id'))
            ->createToken();

        $this->logoutUserPost($response->json('access_token'), true)
            ->assertStatus(self::SUCCESSFUL_LOGOUT_STATUS);

        $this->assertEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should not exist.'
        );
    }

    #[Test]
    public function test_user_cannot_logout_all_their_tokens_with_incorrect_password(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        // Create second token
        User::find($response->json('user.id'))
            ->createToken();

        $this->logoutUserPost($response->json('access_token'), true, self::USER_PASSWORD_DEFAULT . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => ['The provided credentials are incorrect.'],
            ]);

        $this->assertNotEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should exist.'
        );
    }

    #[Test]
    public function test_user_cannot_remove_all_their_tokens_without_password(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        // Create second token
        User::find($response->json('user.id'))
            ->createToken();

        $this->logoutUserPost($response->json('access_token'), true, '')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => ['The password field is required.'],
            ]);

        $this->assertNotEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should exist.'
        );
    }

    #[Test]
    public function test_user_removes_only_their_tokens_when_logout_all_tokens(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        // Create second user
        $this->createUser(self::USER_NICKNAME_DEFAULT . '2', self::USER_EMAIL_DEFAULT . '2');
        $response2 = $this->loginUserPost(nickname: self::USER_NICKNAME_DEFAULT . '2');

        // Logout first user
        $this->logoutUserPost($response->json('access_token'));

        $this->assertEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'User1 token should not exist.'
        );
        $this->assertEquals(
            1,
            $this->findUserTokens($response2->json('user.id'))->count(),
            'User2 Token should exist.'
        );
    }

    #[Test]
    public function test_user_can_logout_all_tokens_with_nickname(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $this->logoutUserWithCredentialsPost(email: ' ')
            ->assertStatus(self::SUCCESSFUL_LOGOUT_STATUS);

        $this->assertEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should not exist.'
        );
    }
    #[Test]
    public function test_user_can_logout_all_tokens_with_email(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $this->logoutUserWithCredentialsPost(logoutWithNickname: false, nickname: ' ')
            ->assertStatus(self::SUCCESSFUL_LOGOUT_STATUS);

        $this->assertEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should not exist.'
        );
    }

    #[Test]
    public function test_user_can_not_logout_all_tokens_without_credentials(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $this->logoutUserWithCredentialsPost(nickname: '', password: '')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'login' => ['The login field is required.'],
                'password' => ['The password field is required.'],
            ]);

        $this->assertNotEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should exist.'
        );
    }

    #[Test]
    public function test_user_can_not_logout_all_tokens_with_incorrect_credentials(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        $this->logoutUserWithCredentialsPost(password: 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'login' => ['The provided credentials are incorrect.'],
                'password' => ['The provided credentials are incorrect.'],
            ]);

        $this->assertNotEquals(
            0,
            $this->findUserTokens($response->json('user.id'))->count(),
            'Tokens should exist.'
        );
    }
}