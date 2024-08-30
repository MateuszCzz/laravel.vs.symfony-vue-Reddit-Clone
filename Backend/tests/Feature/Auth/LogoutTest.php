<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_ROUTE = '/api/auth/login';
    private const LOGOUT_ROUTE = '/api/auth/logout';
    private const LOGOUT_ALL_ROUTE = '/api/auth/logout-all';

    private const TEST_PASSWORD = 'P@ssword1';
    private const TEST_NICKNAME = 'test_user_nickname';
    private const TEST_EMAIL = 'test_user_email@example.com';
    private const TEST_TOKEN_NAME = 'test_token_name';

    /**
     * Make a POST request to perform user logout.
     */
    private function logoutUserPost(string $token, bool $remove_all = false, string $password = self::TEST_PASSWORD): TestResponse
    {
        $data = [];
        if ($remove_all) {
            $data['password'] = $password;
        }

        $route = $remove_all ? self::LOGOUT_ALL_ROUTE : self::LOGOUT_ROUTE;
        return $this->postJson($route, $data, [
            'Authorization' => "Bearer $token",
        ]);
    }

    /**
     * Generate a new user and then Make a POST request to perform login with his data.
     */
    private function generateUserWithLoginPost($nickname = self::TEST_NICKNAME, $email = self::TEST_EMAIL, $password = self::TEST_PASSWORD): TestResponse
    {
        $user = User::factory()->create([
            'nickname' => $nickname,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        return $this->postJson(self::LOGIN_ROUTE, [
            'login' => $user->nickname,
            'password' => $password,
        ]);
    }

    /**
     * Retrieve the token record for a given user ID.
     */
    private function assertTokensInDatabaseByUserId(int $userId, bool $shouldBeInDatabase = true): void
    {
        $tokens = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->get();
        if ($shouldBeInDatabase) {
            $this->assertNotNull($tokens, 'The token should be in the database.');
        } else {
            $this->assertEmpty($tokens, 'The token should be removed from the database.');
        }
    }

    #[Test]
    public function test_user_can_remove_their_token(): void
    {
        $userLoginRequest = $this->generateUserWithLoginPost();
        $userLoginRequest->assertOk();

        $token = $userLoginRequest->json('token');
        $userId = $userLoginRequest->json('user.id');

        $this->assertTokensInDatabaseByUserId($userId);

        $response = $this->logoutUserPost($token);
        $response->assertStatus(205);

        $this->assertTokensInDatabaseByUserId($userId, false);
    }

    #[Test]
    public function test_user_can_remove_all_their_tokens(): void
    {
        $userLoginRequest = $this->generateUserWithLoginPost();
        $userLoginRequest->assertOk();
        $userId = $userLoginRequest->json('user.id');

        User::find($userId)->createToken(self::TEST_TOKEN_NAME);

        $this->assertTokensInDatabaseByUserId($userId);

        $response = $this->logoutUserPost($userLoginRequest->json('token'), true);
        $response->assertStatus(205);

        $this->assertTokensInDatabaseByUserId($userId, false);
    }

    #[Test]
    public function test_user_removes_only_their_tokens_when_removing_all_tokens(): void
    {
        $user1LoginRequest = $this->generateUserWithLoginPost();
        $user2LoginRequest = $this->generateUserWithLoginPost(self::TEST_TOKEN_NAME . '2', '2' . self::TEST_EMAIL);
        $user1Id = $user1LoginRequest->json('user.id');
        $user2Id = $user2LoginRequest->json('user.id');

        User::find($user1Id)->createToken(self::TEST_TOKEN_NAME);

        $this->assertTokensInDatabaseByUserId($user1Id);
        $this->assertTokensInDatabaseByUserId($user2Id);

        $response = $this->logoutUserPost($user1LoginRequest->json('token'), true);
        $response->assertStatus(205);

        $this->assertTokensInDatabaseByUserId($user1Id, false);
        $this->assertTokensInDatabaseByUserId($user2Id);
    }

    #[Test]
    public function test_user_cannot_remove_all_their_tokens_with_incorrect_credentials(): void
    {
        $userLoginRequest = $this->generateUserWithLoginPost();
        $userLoginRequest->assertOk();

        $token = $userLoginRequest->json('token');
        $userId = $userLoginRequest->json('user.id');

        $this->assertTokensInDatabaseByUserId($userId);

        $response = $this->logoutUserPost($token, true, self::TEST_PASSWORD . 'error');
        $response->assertJsonValidationErrors([
            'password' => ['The provided credentials are incorrect.'],
        ]);

        $this->assertTokensInDatabaseByUserId($userId);
    }

    #[Test]
    public function test_user_cannot_remove_all_their_tokens_without_credentials(): void
    {
        $userLoginRequest = $this->generateUserWithLoginPost();
        $userLoginRequest->assertOk();

        $token = $userLoginRequest->json('token');
        $userId = $userLoginRequest->json('user.id');

        $this->assertTokensInDatabaseByUserId($userId);

        $response = $this->logoutUserPost($token, true, '');
        $response->assertJsonValidationErrors([
            'password' => ['The password field is required.'],
        ]);

        $this->assertTokensInDatabaseByUserId($userId);
    }
}