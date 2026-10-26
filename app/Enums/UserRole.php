<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Verified = 'verified';
    case Admin = 'admin';
}
