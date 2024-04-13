<?php

declare(strict_types=1);

namespace App\Api\Post\Entity\Enum;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';
}
