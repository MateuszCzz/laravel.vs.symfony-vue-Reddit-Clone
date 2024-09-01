<?php

namespace App\Enum;

enum TokenName: string
{
    case REMEMBER_ME_ACCESS_TOKEN = 'remember_me_access_token';
    case ACCESS_TOKEN = 'access-token';
    case REFRESH_TOKEN = 'refresh-token';
}