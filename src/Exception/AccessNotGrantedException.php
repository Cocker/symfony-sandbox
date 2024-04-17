<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessNotGrantedException extends HttpException
{
    private function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous, [], $code);
    }

    public static function new(): static
    {
        return new static('Access not granted');
    }
}
