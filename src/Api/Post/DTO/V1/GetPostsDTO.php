<?php

declare(strict_types=1);

namespace App\Api\Post\DTO\V1;

use App\Api\Post\Entity\Enum\PostStatus;
use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class GetPostsDTO extends AbstractDTO
{
    public function __construct(
        public int $page,
        public ?PostStatus $postStatus,
    ) {
        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            page: (int) $request->query->get('page', 1),
            postStatus: PostStatus::tryFrom($request->query->get('status', '')),
        );
    }
}
