<?php

declare(strict_types=1);

namespace App\Api\Post\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class UpdatePostDTO extends AbstractDTO
{
    public string $title;
    public string $body;

    public static function fromRequest(Request $request): static
    {
        $payload = static::requestContentToArray($request);

        $dto = new static;

        $dto->title = $payload['title'] ?? '';
        $dto->body = $payload['body'] ?? '';

        return $dto;
    }
}
