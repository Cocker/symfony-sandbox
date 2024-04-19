<?php

declare(strict_types=1);

namespace App\Api\Post\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class CreatePostDTO extends AbstractDTO
{
    private function __construct(
        public string $title,
        public string $body,
    ) {
        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self(
            title: $payload['title'] ?? '',
            body: $payload['body'] ?? '',
        );
    }
}
