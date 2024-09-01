<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\authHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase, authHelper;

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
    public function test_user_can_remove_all_their_tokens(): void
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
    public function test_user_removes_only_their_tokens_when_removing_all_tokens(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        // Create second user
        $this->createUser(self::USER_NICKNAME . '2', self::USER_EMAIL . '2');
        $response2 = $this->loginUserPost(nickname: self::USER_NICKNAME . '2');

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
    public function test_user_cannot_remove_all_their_tokens_with_incorrect_credentials(): void
    {
        $this->createUser();
        $response = $this->loginUserPost();

        // Create second token
        User::find($response->json('user.id'))
            ->createToken();

        $this->logoutUserPost($response->json('access_token'), true, self::USER_PASSWORD . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => ['The provided credentials are incorrect.'],
            ]);

        $this->assertGreaterThan(
            1,
            $this->findUserTokens($response->json('user.id'))->count(),
            'There should be more than one token.'
        );
    }

    #[Test]
    public function test_user_cannot_remove_all_their_tokens_without_credentials(): void
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

        $this->assertGreaterThan(
            1,
            $this->findUserTokens($response->json('user.id'))->count(),
            'There should be more than one token.'
        );
    }
}