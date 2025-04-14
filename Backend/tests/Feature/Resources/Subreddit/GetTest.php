<?php

namespace Tests\Feature\Resources\Subreddit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Traits\AuthHelper;
use Tests\Feature\Traits\SubredditHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetTest extends TestCase
{
    use RefreshDatabase, AuthHelper, SubredditHelper;

    public static function validSubredditNameProvider(): array
    {
        return [
            '' => [
                'name' => '',
            ],
        ];
    }

    public static function invalidSubredditNameProvider(): array
    {
        return [
            '' => [
                'name' => '',
                'expectedStatus' => '',
                'expectedValidationError' => '',
            ],
        ];
    }

    #[Test]
    #[DataProvider('validSubredditNameProvider')]
    public function test_can_get_subreddit_by_valid_name(): void
    {

    }

    #[Test]
    #[DataProvider('invalidSubredditNameProvider')]
    public function test_cannot_get_subreddit_with_invalid_name(): void
    {

    }

    #[Test]
    public function test_cannot_get_subreddit_by_id(): void
    {

    }

    #[Test]
    public function test_show_public_subreddit_to_guest(): void
    {

    }

    #[Test]
    public function test_show_public_subreddit_to_user(): void
    {

    }

    #[Test]
    public function test_show_public_subreddit_to_creator(): void
    {

    }

    #[Test]
    public function test_dont_show_private_subreddit_to_guest(): void
    {

    }

    #[Test]
    public function test_dont_show_private_subreddit_to_regular_user(): void
    {

    }

    #[Test]
    public function test_show_private_subreddit_to_creator(): void
    {

    }

    #[Test]
    public function test_show_only_public_parameters_to_guest(): void
    {

    }

    #[Test]
    public function test_show_only_public_parameters_to_user(): void
    {

    }

    #[Test]
    public function test_show_all_parameters_to_creator(): void
    {

    }

    // structure tests
}