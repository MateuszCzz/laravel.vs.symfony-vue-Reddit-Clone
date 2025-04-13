<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Traits\AuthHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    private const REGISTER_ROUTE = '/api/auth/register';
    private const SUCCESSFUL_REGISTER_STATUS = 201;

    /**
     * Make a POST request to register with user data.
     *
     * @param string $nickname The nickname of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @return TestResponse The response from the register request.
     */
    private function registerUserPost(string $nickname = self::USER_NICKNAME_DEFAULT, string $email = self::USER_EMAIL_DEFAULT, string $password = self::USER_PASSWORD_DEFAULT, string $passwordConfirm = self::USER_PASSWORD_DEFAULT): TestResponse
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
        $this->registerUserPost()
            ->assertJsonStructure(self::SUCCESSFUL_AUTH_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_REGISTER_STATUS);
    }

    #[Test]
    public function test_successful_registration_creates_new_user_in_database(): void
    {
        $this->registerUserPost();
        $this->assertDatabaseHas('users', [
            'nickname' => self::USER_NICKNAME_DEFAULT,
            'email' => self::USER_EMAIL_DEFAULT,
        ]);
    }

    #[Test]
    public function test_user_cannot_register_with_duplicate_nickname(): void
    {
        $this->createUser();
        $this->registerUserPost()
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname has already been taken.'
            ]);
    }

    #[Test]
    public function test_user_cannot_register_with_too_short_or_too_long_nickname(): void
    {
        $this->registerUserPost(self::TEST_NICKNAME_SHORT)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field must be at least 3 characters.'
            ]);

        $this->registerUserPost(self::TEST_NICKNAME_LONG)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field must not be greater than 20 characters.'
            ]);
    }

    #[Test]
    public function test_user_cannot_register_with_special_characters_in_nickname(): void
    {
        $this->registerUserPost(self::TEST_NICKNAME_SPECIAL_CHARACTERS)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field must only contain letters, numbers, dashes, and underscores.'
            ]);
    }

    #[Test]
    public function test_user_cannot_register_with_missing_credentials(): void
    {
        $this->registerUserPost(password: '', passwordConfirm: '')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => 'The password field is required.'
            ]);

        $this->registerUserPost('')
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field is required.'
            ]);
    }

    #[Test]
    public function test_user_cannot_register_with_mismatched_passwords(): void
    {
        $this->registerUserPost(passwordConfirm: self::USER_PASSWORD_DEFAULT . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => 'The password field confirmation does not match.'
            ]);
    }
}
