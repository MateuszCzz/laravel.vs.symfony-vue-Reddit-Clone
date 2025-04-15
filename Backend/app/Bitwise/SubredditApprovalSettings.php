<?php

namespace App\Bitwise;

class SubredditApprovalSettings extends BitwiseFlag
{
    // Whenever application should await approval before making entities public
    public const FLAG_MEMBERS_AWAIT_APPROVAL = 1 << 0; // 1
    public const FLAG_POSTS_AWAIT_APPROVAL = 1 << 1; // 2
    public const FLAG_COMMENTS_AWAIT_APPROVAL = 1 << 2; // 4

    public static function getFlags(): array
    {
        return [
            'members_should_await_approval' => [
                'value' => self::FLAG_MEMBERS_AWAIT_APPROVAL,
                'description' => 'New members require approval'
            ],
            'posts_should_await_approval' => [
                'value' => self::FLAG_POSTS_AWAIT_APPROVAL,
                'description' => 'New posts require approval'
            ],
            'comments_should_await_approval' => [
                'value' => self::FLAG_COMMENTS_AWAIT_APPROVAL,
                'description' => 'New comments require approval'
            ],
        ];
    }
}