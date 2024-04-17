<?php

declare(strict_types=1);

namespace App\Api\Post\DTO\V1;

use App\Api\Post\Entity\Enum\PostStatus;
use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class GetPostsDTO extends AbstractDTO
{
    public int $page;
    public ?PostStatus $postStatus;

    public static function fromRequest(Request $request): static
    {
        $dto = new static;

        $dto->page = (int) $request->query->get('page', 1);
        $dto->postStatus = PostStatus::tryFrom($request->query->get('status', ''));

        return $dto;
    }
}
