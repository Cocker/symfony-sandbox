<?php

declare(strict_types=1);

namespace App\Api\Post\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class CreatePostCommentDTO extends AbstractDTO
{
    public function __construct(public string $content) {
        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self($payload['content'] ?? '');
    }
}
