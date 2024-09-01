<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Traits\authHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, authHelper;

    private const GENERATE_NICKNAME_ROUTE = '/api/auth/generate-nickname';
    private const REGISTER_ROUTE = '/api/auth/register';
    private const SUCCESSFUL_REGISTER_STATUS = 201;
    private const TEST_NICKNAME_SHORT = 'te';
    private const TEST_NICKNAME_LONG = 'test_user_nickname_that_is_too_long';
    private const TEST_NICKNAME_SPECIAL_CHARACTERS = 'test_user_nickname$#';

    /**
     * Make a POST request to register with user data.
     *
     * @param string $nickname The nickname of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @return TestResponse The response from the register request.
     */
    private function registerUserPost(string $nickname = self::USER_NICKNAME, string $email = self::USER_EMAIL, string $password = self::USER_PASSWORD, string $passwordConfirm = self::USER_PASSWORD): TestResponse
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
            'nickname' => self::USER_NICKNAME,
            'email' => self::USER_EMAIL,
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
        $this->registerUserPost(passwordConfirm: self::USER_PASSWORD . 'error')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'password' => 'The password field confirmation does not match.'
            ]);
    }

    #[Test]
    public function test_user_can_check_if_nickname_is_available(): void
    {
        $this->nicknameCheckGet()
            ->assertJsonStructure(self::SUCCESSFUL_NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function test_nickname_availability_when_taken(): void
    {
        $this->createUser();
        $this->nicknameCheckGet()
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname has already been taken.'
            ]);
    }

    #[Test]
    public function test_nickname_availability_for_nothing(): void
    {
        $this->nicknameCheckGet(' ')
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field is required.'
            ]);
    }

    #[Test]
    public function test_nickname_availability_with_special_characters(): void
    {
        $this->nicknameCheckGet(self::TEST_NICKNAME_SPECIAL_CHARACTERS)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field must only contain letters, numbers, dashes, and underscores.'
            ]);
    }

    #[Test]
    public function test_nickname_availability_with_wrong_nickname_length(): void
    {
        $this->nicknameCheckGet(self::TEST_NICKNAME_SHORT)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field must be at least 3 characters.'
            ]);

        $this->nicknameCheckGet(self::TEST_NICKNAME_LONG)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => 'The nickname field must not be greater than 20 characters.'
            ]);
    }

    #[Test]
    public function test_user_can_generate_unique_nickname(): void
    {
        $response = $this->postJson(self::GENERATE_NICKNAME_ROUTE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);

        $this->nicknameCheckGet($response->json('nickname'))
            ->assertJsonStructure(self::SUCCESSFUL_NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function test_user_can_generate_unique_nickname_when_large_number_of_users(): void
    {
        User::factory()->createMany(100);
        $response = $this->postJson(self::GENERATE_NICKNAME_ROUTE);

        $this->nicknameCheckGet($response->json('nickname'))
            ->assertJsonStructure(self::SUCCESSFUL_NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }
}
