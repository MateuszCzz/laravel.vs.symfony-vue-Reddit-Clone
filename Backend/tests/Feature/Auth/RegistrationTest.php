<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER_ROUTE = '/api/auth/register';
    private const CHECK_NICKNAME_ROUTE = '/api/auth/check-nickname/';
    private const GENERATE_NICKNAME_ROUTE = '/api/auth/generate-nickname';

    private const TEST_PASSWORD = 'P@ssword1';
    private const TEST_NICKNAME = 'test_user_nickname';
    private const TEST_EMAIL = 'test_user_email@example.com';
    private const TEST_NICKNAME_SHORT = 'te';
    private const TEST_NICKNAME_LONG = 'test_user_nickname_that_is_too_long';
    private const TEST_NICKNAME_SPECIAL_CHARACTERS = 'test_user_nickname$#';

    /**
     * Make a POST request to preform user registration.
     */
    private function registerUserPost(string $nickname = self::TEST_NICKNAME, string $email = self::TEST_EMAIL, string $password = self::TEST_PASSWORD, string $passwordConfirm = self::TEST_PASSWORD): TestResponse
    {
        return $this->postJson(self::REGISTER_ROUTE, [
            'nickname' => $nickname,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirm,
        ]);
    }

    #[Test]
    public function test_user_can_register(): void
    {
        $response = $this->registerUserPost();
        $response->assertJsonStructure([
            'user' => ['id', 'nickname', 'email'],
            'token',
        ]);
    }

    #[Test]
    public function test_successful_registration_creates_new_user_in_database(): void
    {
        $this->registerUserPost();

        $this->assertDatabaseHas('users', [
            'nickname' => self::TEST_NICKNAME,
            'email' => self::TEST_EMAIL,
        ]);
    }

    #[Test]
    public function test_user_cannot_register_with_duplicate_nickname(): void
    {
        User::factory()->create([
            'nickname' => self::TEST_NICKNAME,
            'email' => self::TEST_EMAIL,
        ]);
        $response = $this->registerUserPost();

        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname has already been taken.'
        ]);
    }

    #[Test]
    public function test_user_cannot_register_with_too_short_or_too_long_nickname(): void
    {
        $response = $this->registerUserPost(self::TEST_NICKNAME_SHORT);
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must be at least 3 characters.'
        ]);

        $response = $this->registerUserPost(self::TEST_NICKNAME_LONG);
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must not be greater than 20 characters.'
        ]);
    }

    #[Test]
    public function test_user_cannot_register_with_special_characters_in_nickname(): void
    {
        $response = $this->registerUserPost(self::TEST_NICKNAME_SPECIAL_CHARACTERS);
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must only contain letters, numbers, dashes, and underscores.'
        ]);
    }

    #[Test]
    public function test_user_cannot_register_with_missing_credentials(): void
    {
        $response = $this->registerUserPost(self::TEST_NICKNAME, self::TEST_EMAIL, '', '');

        $response->assertJsonValidationErrors([
            'password' => 'The password field is required.'
        ]);

        $response = $this->registerUserPost('');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field is required.'
        ]);
    }

    #[Test]
    public function test_user_can_check_if_nickname_is_available(): void
    {
        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME);

        $response->assertOk()
            ->assertJson([
                'available' => true
            ]);
    }

    #[Test]
    public function test_nickname_availability_when_taken(): void
    {
        User::factory()->create([
            'nickname' => self::TEST_NICKNAME,
        ]);

        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME);

        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname has already been taken.'
        ]);
    }

    #[Test]
    public function test_nickname_availability_for_nothing(): void
    {
        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . ' ');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field is required.'
        ]);
    }

    #[Test]
    public function test_nickname_availability_with_special_characters(): void
    {
        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME_SPECIAL_CHARACTERS);
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must only contain letters, numbers, dashes, and underscores.'
        ]);
    }

    #[Test]
    public function test_nickname_availability_with_too_short_or_too_long_nickname(): void
    {
        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME_SHORT);
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must be at least 3 characters.'
        ]);
        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME_LONG);
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must not be greater than 20 characters.'
        ]);
    }

    #[Test]
    public function test_user_can_generate_unique_nickname(): void
    {
        $nicknameRequest = $this->postJson(self::GENERATE_NICKNAME_ROUTE);
        $nicknameRequest->assertOk();

        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . $nicknameRequest->json('nickname'));
        $response->assertOk()
            ->assertJson([
                'available' => true
            ]);
    }

    #[Test]
    public function test_user_can_generate_unique_nickname_when_large_number_of_users(): void
    {
        User::factory()->createMany(100);
        $nicknameRequest = $this->postJson('/api/auth/generate-nickname/');

        $response = $this->getJson(self::CHECK_NICKNAME_ROUTE . $nicknameRequest->json('nickname'));
        $response->assertOk()
            ->assertJson([
                'available' => true
            ]);
    }

}
