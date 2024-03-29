<?php

namespace App\Api\User\Entity\Enum;

enum UserStatus: string
{
    case UNVERIFIED = 'unverified';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
}
