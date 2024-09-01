<?php

namespace App\Enum;

enum TokenAbility: string
{
    case REFRESH_EXPIRATION = 'refresh-tokens';
    case ACCESS_API = 'access-api';
}