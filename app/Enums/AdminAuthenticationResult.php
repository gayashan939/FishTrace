<?php

namespace App\Enums;

enum AdminAuthenticationResult: string
{
    case AUTHENTICATED = 'AUTHENTICATED';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case ROLE_REQUIRED = 'ROLE_REQUIRED';
}
