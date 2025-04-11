<?php

namespace App\Enum;

enum SubredditType: string {
    case PUBLIC = 'PUBLIC';
    case PRIVATE = 'PRIVATE';
    // case RESTRICTED = 'RESTRICTED';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
