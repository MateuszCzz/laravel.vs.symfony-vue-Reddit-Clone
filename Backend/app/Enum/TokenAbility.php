<?php

namespace App\Enum;

enum TokenAbility: string
{
    case REFRESH_EXPIRATION = 'refresh-expiration';
    case ACCESS_API = 'access-api';
}