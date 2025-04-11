<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Feature\Traits\authHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class NicknameTest extends TestCase
{
    use RefreshDatabase, authHelper;

    private const GENERATE_NICKNAME_ROUTE = '/api/auth/generate-nickname';

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
