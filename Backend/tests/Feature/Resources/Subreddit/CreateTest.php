<?php

namespace Tests\Feature\Resources\Subreddit;

use App\Enum\SubredditType;
use Tests\Feature\Traits\AuthHelper;
use Tests\Feature\Traits\SubredditHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Testing\TestResponse;

class CreateTest extends TestCase
{
    use RefreshDatabase, AuthHelper, SubredditHelper;

    private const SUBREDDIT_DESCRIPTION_DEFAULT = 'My new community about people using phpunit.';
    private const TEST_SUBREDDIT_NAME_SHORT = 'Su';
    private const TEST_SUBREDDIT_NAME_LONG = 'NameThatExcesedTheLimitOfCharacters';
    private const TEST_SUBREDDIT_NAME_SPECIAL_CHARACTERS = 'S@bre$$it!';
    private const TEST_SUBREDDIT_TYPE = 'invalid_type';

    private const AUTH_FAILED_STATUS = 401;

    /**
     * Make a POST request to create a new subreddit.
     *
     * @param string|null $token The authorization token of the user performing the request.
     * @param string $name The name of the subreddit (default: SUBREDDIT_NAME).
     * @param string $description The description of the subreddit (default: SUBREDDIT_DESCRIPTION).
     * @param \App\Enum\SubredditType $type The type of subreddit (default: PUBLIC).
     * @param bool $isNsfw Whether the subreddit is NSFW (default: false).
     * @return \Illuminate\Testing\TestResponse The response from the creation request.
     */
    private function createSubredditPost(?string $token = null, string $name = self::SUBREDDIT_NAME_DEFAULT, string $description = self::SUBREDDIT_DESCRIPTION_DEFAULT, SubredditType $type = SubredditType::PUBLIC , bool $isNsfw = false): TestResponse
    {
        return $this->postJson(self::SUBREDDIT_CREATION_ROUTE, [
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'is_nsfw' => $isNsfw,
        ], [
            'Authorization' => $token ? "Bearer $token" : null,
            'Accept' => 'application/json',
        ]);
    }

    #[Test]
    public function test_user_can_create_subreddit(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost($token)
            ->assertStatus(self::SUCCESSFUL_CREATION_STATUS)
            ->assertJsonStructure(self::SUCCESSFUL_SUBREDDIT_CREATION_JSON_STRUCTURE);
    }

    #[Test]
    public function test_successful_creation_creates_new_subreddit_in_database(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost($token);

        $this->assertDatabaseHas('subreddits', [
            'name' => self::SUBREDDIT_NAME_DEFAULT,
        ]);

        $this->assertDatabaseCount('subreddits', 1);
    }


    #[Test]
    public function test_creator_is_set_to_authenticated_user(): void
    {
        $user = $this->createUser();
        $token = $this->createAccessToken($user);

        $this->createSubredditPost($token);

        $this->assertDatabaseHas("subreddits", ["creator_id" => $user->id]);

        $this->assertDatabaseCount('subreddits', 1);
    }

    #[Test]
    public function test_creator_is_added_as_member(): void
    {
        $user = $this->createUser();
        $token = $this->createAccessToken($user);

        $response = $this->createSubredditPost($token);
        $subredditId = $response->json('subreddit.id');

        $this->assertDatabaseHas("memberships", [
            "member_id" => $user->id,
            "subreddit_id" => $subredditId
        ]);

        $this->assertDatabaseCount('memberships', 1);
    }

    #[Test]
    public function test_subreddit_parameters_are_set_to_provided_values(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost(
            token: $token,
            name: self::SUBREDDIT_NAME_DEFAULT,
            description: self::SUBREDDIT_DESCRIPTION_DEFAULT,
            type: SubredditType::PRIVATE ,
            isNsfw: true,
        );

        $this->assertDatabaseHas('subreddits', [
            'name' => self::SUBREDDIT_NAME_DEFAULT,
            'description' => self::SUBREDDIT_DESCRIPTION_DEFAULT,
            'type' => SubredditType::PRIVATE ,
            'is_nsfw' => true,
        ]);

        $this->assertDatabaseCount('subreddits', 1);

    }

    #[Test]
    public function test_subreddit_parameters_are_set_to_default_when_not_provided(): void
    {
        $token = $this->createAccessToken();

        $this->postJson(self::SUBREDDIT_CREATION_ROUTE, [
            'name' => self::SUBREDDIT_NAME_DEFAULT,
        ], [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->assertStatus(self::SUCCESSFUL_CREATION_STATUS)
            ->assertJsonStructure(self::SUCCESSFUL_SUBREDDIT_CREATION_JSON_STRUCTURE);

        $this->assertDatabaseHas('subreddits', [
            'name' => self::SUBREDDIT_NAME_DEFAULT,
            'description' => null,
            'type' => SubredditType::PUBLIC ,
            'is_nsfw' => false,
        ]);

        $this->assertDatabaseCount('subreddits', 1);
    }

    #[Test]
    public function test_subreddit_creation_fails_when_not_authenticated(): void
    {
        $this->createSubredditPost()
            ->assertStatus(self::AUTH_FAILED_STATUS);
    }

    #[Test]
    public function test_subreddit_creation_fails_when_name_is_not_unique(): void
    {
        $this->createSubreddit(name: self::SUBREDDIT_NAME_DEFAULT);

        $token = $this->createAccessToken();

        $this->createSubredditPost($token, self::SUBREDDIT_NAME_DEFAULT)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['name']);
        ;
    }

    #[Test]
    public function test_subreddit_creation_fails_when_name_is_too_short(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost($token, self::TEST_SUBREDDIT_NAME_SHORT)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['name']);
    }


    #[Test]
    public function test_subreddit_creation_fails_when_name_is_too_long(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost($token, self::TEST_SUBREDDIT_NAME_LONG)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['name']);
        ;
    }


    #[Test]
    public function test_subreddit_creation_fails_when_name_contains_special_characters(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost($token, self::TEST_SUBREDDIT_NAME_SPECIAL_CHARACTERS)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function test_subreddit_creation_fails_when_name_is_not_provided(): void
    {
        $token = $this->createAccessToken();

        $this->createSubredditPost($token, "")
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function test_subreddit_creation_fails_when_nsfw_is_not_boolean(): void
    {
        $token = $this->createAccessToken();

        $this->postJson(self::SUBREDDIT_CREATION_ROUTE, [
            'name' => self::SUBREDDIT_NAME_DEFAULT,
            'is_nsfw' => 5,
        ], [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['is_nsfw']);
    }

    #[Test]
    public function test_subreddit_creation_fails_when_type_is_invalid(): void
    {
        $token = $this->createAccessToken();

        $this->postJson(self::SUBREDDIT_CREATION_ROUTE, [
            'name' => self::SUBREDDIT_NAME_DEFAULT,
            'type' => self::TEST_SUBREDDIT_TYPE
        ], [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function test_subreddit_creation_fails_when_description_is_too_long(): void
    {
        $token = $this->createAccessToken();

        // Prepare a description that exceeds character limit
        $TEST_SUBREDDIT_DESCRIPTION_LONG = str_repeat('A', 502);

        $this->createSubredditPost(token: $token, description: $TEST_SUBREDDIT_DESCRIPTION_LONG)
            ->assertStatus(self::VALIDATION_ERROR_STATUS)
            ->assertJsonValidationErrors(['description']);
    }
}
