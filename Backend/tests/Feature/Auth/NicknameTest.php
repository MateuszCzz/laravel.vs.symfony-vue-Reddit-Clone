<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Feature\Traits\AuthHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class NicknameTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    // Routes
    private const CHECK_NICKNAME_ROUTE = '/api/auth/check-nickname/';
    private const GENERATE_NICKNAME_ROUTE = '/api/auth/generate-nickname';

    // Validation messages
    private const NICKNAME_TAKEN_MESSAGE = 'The nickname has already been taken.';
    private const NICKNAME_INVALID_CHARS_MESSAGE = 'The nickname field must only contain letters, numbers, dashes, and underscores.';
    private const NICKNAME_TOO_LONG_MESSAGE = 'The nickname field must not be greater than 20 characters.';
    private const NICKNAME_TOO_SHORT_MESSAGE = 'The nickname field must be at least 3 characters.';

    // JSON structures
    private const NICKNAME_CHECK_JSON_STRUCTURE = [
        'available',
        'nickname',
    ];

    // Status codes
    private const SUCCESSFUL_NICKNAME_CHECK_STATUS = 200;

    #[Test]
    public function test_guest_can_check_nickname_availability(): void
    {

        $this->getJson(self::CHECK_NICKNAME_ROUTE . self::USER_NICKNAME_DEFAULT)
            ->assertJsonStructure(self::NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function test_nickname_check_fails_when_nickname_is_taken(): void
    {
        $this->createUser();
        $this->getJson(self::CHECK_NICKNAME_ROUTE . self::USER_NICKNAME_DEFAULT)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => self::NICKNAME_TAKEN_MESSAGE
            ]);
    }

    #[Test]
    public function test_nickname_check_fails_when_nickname_has_special_characters(): void
    {
        $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME_SPECIAL_CHARACTERS)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => self::NICKNAME_INVALID_CHARS_MESSAGE
            ]);
    }

    #[Test]
    public function test_nickname_check_fails_when_nickname_is_too_short(): void
    {
        $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME_SHORT)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => self::NICKNAME_TOO_SHORT_MESSAGE
            ]);
    }

    #[Test]
    public function test_nickname_check_fails_when_nickname_is_too_long(): void
    {
        $this->getJson(self::CHECK_NICKNAME_ROUTE . self::TEST_NICKNAME_LONG)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'nickname' => self::NICKNAME_TOO_LONG_MESSAGE
            ]);
    }

    #[Test]
    public function test_user_can_generate_unique_nickname(): void
    {
        $response = $this->postJson(self::GENERATE_NICKNAME_ROUTE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);

        $this->getJson(self::CHECK_NICKNAME_ROUTE . $response->json('nickname'))
            ->assertJsonStructure(self::NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }

    #[Test]
    public function test_user_can_generate_unique_nickname_when_many_names_are_taken(): void
    {
        User::factory()->createMany(500);
        $response = $this->postJson(self::GENERATE_NICKNAME_ROUTE);

        $this->getJson(self::CHECK_NICKNAME_ROUTE . $response->json('nickname'))
            ->assertJsonStructure(self::NICKNAME_CHECK_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_NICKNAME_CHECK_STATUS);
    }
}