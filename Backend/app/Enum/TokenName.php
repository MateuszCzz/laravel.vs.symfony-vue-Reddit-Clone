<?php

namespace App\Enum;

enum TokenName: string
{
    case ACCESS_TOKEN = 'access-token';
    case REMEMBER_ME_ACCESS_TOKEN = 'remember_me_access_token';
    case REFRESH_TOKEN = 'refresh-token';
    case  REMEMBER_ME_REFRESH_TOKEN = 'remember_me_refresh_token';
}