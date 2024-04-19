<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EntityNotFoundException extends HttpException
{
    protected function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_NOT_FOUND, $message, $previous, [], $code);
    }

    public static function new(string $entityClassName, string $ulid): self
    {
        $entityName = class_basename($entityClassName);

        return new self("Entity $entityName with ulid $ulid not found");
    }
}
