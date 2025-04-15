<?php

namespace Tests\Feature\Auth;

use Illuminate\Testing\TestResponse;
use Tests\Feature\Traits\AuthHelper;
use Tests\Feature\Traits\SubredditHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class NameTest extends TestCase
{
    use RefreshDatabase, AuthHelper, SubredditHelper;

    // Test data
    private const TEST_NAME_SPECIAL_CHARACTERS = "n!<kn@me$";
    private const TEST_NAME_SHORT = 'te';
    private const TEST_NAME_LONG = 'test_name_that_is_too_long_';

    // Routes
    private const CHECK_NAME_ROUTE = '/api/subreddits/check-name/';

    // Validation messages
    private const NAME_TAKEN_MESSAGE = 'The name has already been taken.';
    private const NAME_INVALID_CHARS_MESSAGE = 'The name field must only contain letters, numbers, dashes, and underscores.';
    private const NAME_TOO_LONG_MESSAGE = 'The name field must not be greater than 21 characters.';
    private const NAME_TOO_SHORT_MESSAGE = 'The name field must be at least 3 characters.';

    // JSON structures
    private const NAME_CHECK_JSON_STRUCTURE = [
        'available',
        'name',
    ];

    // Status codes
    private const SUCCESSFUL_NAME_CHECK_STATUS = 200;

    /**
     * Make a GET request to check given name availability.
     * 
     * @param string $name The name to be checked.
     * @param string|null $token the authorization token to be included in the request header.
     * @return \Illuminate\Testing\TestResponse The response from the name-check request.
     */

    private function nameCheckGet(string $name = self::SUBREDDIT_NAME_DEFAULT, ?string $token = null): TestResponse
    {
        return $this->getJson(self::CHECK_NAME_ROUTE . $name, [
            'Authorization' => "Bearer $token",
        ]);
    }

    #[Test]
    public function test_user_can_check_name_aviability(): void
    {
        $token = $this->createAccessToken();
        $this->nameCheckGet(token: $token)
            ->assertStatus(self::SUCCESSFUL_NAME_CHECK_STATUS)
            ->assertJsonStructure(self::NAME_CHECK_JSON_STRUCTURE);
    }

    #[Test]
    public function test_name_check_fails_when_name_is_taken(): void
    {
        $this->createSubreddit(name: self::SUBREDDIT_NAME_DEFAULT);

        $token = $this->createAccessToken();
        $this->nameCheckGet(token: $token)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'name' => self::NAME_TAKEN_MESSAGE
            ]);
    }

    #[Test]
    public function test_name_check_fails_when_not_authenticated(): void
    {
        $this->nameCheckGet()
            ->assertStatus(self::AUTH_ERROR_STATUS);
    }

    #[Test]
    public function test_name_check_fails_when_name_has_special_chars(): void
    {
        $token = $this->createAccessToken();
        $this->nameCheckGet(self::TEST_NAME_SPECIAL_CHARACTERS, $token)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'name' => self::NAME_INVALID_CHARS_MESSAGE
            ]);
    }

    #[Test]
    public function test_name_check_fails_with_too_long_name(): void
    {
        $token = $this->createAccessToken();
        $this->nameCheckGet(self::TEST_NAME_LONG, $token)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'name' => self::NAME_TOO_LONG_MESSAGE
            ]);
    }

    #[Test]
    public function test_name_check_fails_with_too_short_name(): void
    {
        $token = $this->createAccessToken();
        $this->nameCheckGet(self::TEST_NAME_SHORT, $token)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors([
                'name' => self::NAME_TOO_SHORT_MESSAGE
            ]);
    }
}