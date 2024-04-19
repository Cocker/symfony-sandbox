<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessNotGrantedException extends HttpException
{
    protected function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous, [], $code);
    }

    public static function new(): self
    {
        return new self('Access not granted');
    }
}
