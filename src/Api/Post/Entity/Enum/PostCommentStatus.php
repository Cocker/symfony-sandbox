<?php

declare(strict_types=1);

namespace App\Api\Post\Entity\Enum;

enum PostCommentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
