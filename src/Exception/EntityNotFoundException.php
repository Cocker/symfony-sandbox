<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EntityNotFoundException extends HttpException
{
    private function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_NOT_FOUND, $message, $previous, [], $code);
    }

    public static function new(string $entityClassName, string $ulid): static
    {
        $entityName = class_basename($entityClassName);

        return new static("Entity $entityName with ulid $ulid not found");
    }
}
