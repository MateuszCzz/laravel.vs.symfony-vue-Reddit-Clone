<?php

namespace App\Enum;

enum PostType: string {
    case TEXT = 'TEXT';
    case IMAGE = 'IMAGE';
    case LINK = 'LINK';
    case REPOST = 'REPOST';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
