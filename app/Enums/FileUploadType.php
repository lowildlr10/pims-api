<?php

namespace App\Enums;

enum FileUploadType: string
{
    case AVATAR = 'avatar';
    case SIGNATURE = 'signature';
    case LOGO = 'logo';
    case FAVICON = 'favicon';
    case LOGIN_BACKGROUND = 'login-background';
}
