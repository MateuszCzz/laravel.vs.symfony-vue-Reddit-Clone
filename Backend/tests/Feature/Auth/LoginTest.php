<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_ROUTE = '/api/auth/login';

    private const TEST_PASSWORD = 'P@ssword1';
    private const TEST_NICKNAME = 'test_user_nickname';
    private const TEST_EMAIL = 'test_user_email@example.com';
    private const TEST_TOKEN_NAME = 'test_token_name';

    /**
     * Generate a new user.
     */
    private function generateUser($nickname = self::TEST_NICKNAME, $email = self::TEST_EMAIL, $password = self::TEST_PASSWORD): User
    {
        return User::factory()->create([
            'nickname' => $nickname,
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     *  Make a POST request to perform login with user data.
     */
    private function loginUserPost($loginWithNickname = true, $nickname = self::TEST_NICKNAME, $email = self::TEST_EMAIL, $password = self::TEST_PASSWORD, $rememberMe = false): TestResponse
    {
        return $this->postJson(self::LOGIN_ROUTE, [
            'login' => $loginWithNickname ? $nickname : $email,
            'password' => $password,
            'remember_me' => $rememberMe,
        ]);
    }

    #[Test]
    public function test_user_can_login_with_nickname(): void
    {
        $this->generateUser();
        $this->loginUserPost()
            ->assertJsonStructure([
                'user' => ['id', 'nickname', 'email'],
                'token',
            ]);
    }

    #[Test]
    public function test_user_can_login_with_email(): void
    {
        $this->generateUser();
        $this->loginUserPost(false)
            ->assertJsonStructure([
                'user' => ['id', 'nickname', 'email'],
                'token',
            ]);
    }

    #[Test]
    public function test_successful_login_creates_new_token_in_database(): void
    {
        $this->generateUser();
        $response = $this->loginUserPost();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $response->json('user.id'),
            'tokenable_type' => 'App\\Models\\User',
            'name' => 'access-token',
        ]);
    }

    #[Test]
    public function test_generated_tokens_are_time_limited(): void
    {
        $this->generateUser();
        $response = $this->loginUserPost();

        $token = \DB::table('personal_access_tokens')->where('tokenable_id', $response->json('user.id'))->first();
        $this->assertNotNull($token->expires_at, 'The token should have an expiration time set.');

        $expiresAt = Carbon::parse($token->expires_at);
        $this->assertTrue($expiresAt->lessThanOrEqualTo(Carbon::now()->addHour()), 'The token should expire within 1 hour.');
    }

    #[Test]
    public function test_user_cannot_login_with_unregistered_nickname_or_email(): void
    {
        $this->loginUserPost(false, self::TEST_NICKNAME, self::TEST_EMAIL . '2')->assertJsonValidationErrors([
            'login' => 'The provided credentials are incorrect.',
            'password' => 'The provided credentials are incorrect.'
        ]);

        $this->loginUserPost(true, self::TEST_NICKNAME . '2')
            ->assertJsonValidationErrors([
                'login' => 'The provided credentials are incorrect.',
                'password' => 'The provided credentials are incorrect.'
            ]);
    }

    #[Test]
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $this->generateUser();
        $this->loginUserPost(true, self::TEST_NICKNAME . '2')
            ->assertJsonValidationErrors([
                'login' => 'The provided credentials are incorrect.',
                'password' => 'The provided credentials are incorrect.'
            ]);

    }

    #[Test]
    public function test_user_cannot_login_without_credentials(): void
    {
        $this->generateUser();
        $this->loginUserPost(true, self::TEST_NICKNAME, self::TEST_EMAIL, '')
            ->assertJsonValidationErrors([
                'password' => 'The password field is required.'
            ]);

    }

    #[Test]
    public function test_user_can_generate_remember_me_token(): void
    {
        $this->generateUser();
        $response = $this->loginUserPost(true, self::TEST_NICKNAME, self::TEST_EMAIL, self::TEST_PASSWORD, true)
            ->assertJsonStructure([
                'user' => ['id', 'nickname', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $response->json('user.id'),
            'tokenable_type' => 'App\\Models\\User',
            'name' => 'remember_me_access_token'
        ]);
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_successful_login_attempt(): void
    // {
    //     $this->assertTrue(true);
    // }
}
