<?php

namespace Tests\Feature\Traits;

use App\Enum\SubredditType;

use App\Models\Subreddit;
use App\Models\User;

trait SubredditHelper
{
    // Subreddit data
    private const SUBREDDIT_NAME_DEFAULT = 'Subreddit1';

    // Routes
    private const SUBREDDIT_RESOURCE_ROUTE = '/api/subreddits/';

    // Status codes
    private const SUCCESSFUL_CREATION_STATUS = 201;
    private const SUCCESSFUL_FETCH_STATUS = 200;
    private const VALIDATION_ERROR_STATUS = 422;

    // JSON structures
    private const SUCCESSFUL_SUBREDDIT_CREATION_JSON_STRUCTURE = [
        'subreddit' => [
            'id',
            'name',
            'description',
            'type',
            'is_nsfw',
            'creator_id',
            'created_at',
            'updated_at',
        ],
        'membership' => [
            'member_id',
            'subreddit_id',
        ],
    ];

    // Methods
    /**
     * Generate a new subreddit.
     *
     * @param \App\Models\User|null $user The user who creates the subreddit (null = auto-create user).
     * @param string $name The name of the subreddit (default: SUBREDDIT_NAME).
     * @param SubredditType $type The type of subreddit (default: PUBLIC).
     * @param bool $isNsfw Whether the subreddit is NSFW (default: false).
     * @return \App\Models\Subreddit The created Subreddit instance.
     */
    private function createSubreddit(?User $user = null, string $name = self::SUBREDDIT_NAME_DEFAULT, SubredditType $type = SubredditType::PUBLIC , bool $isNsfw = false): Subreddit
    {
        $creator = $user ?? User::factory()->create();

        return Subreddit::factory()->create([
            'creator_id' => $creator->id,
            'name' => $name,
            'type' => $type,
            'is_nsfw' => $isNsfw,
        ]);
    }
}
