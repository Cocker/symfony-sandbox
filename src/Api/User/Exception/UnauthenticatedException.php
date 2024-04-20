<?php

namespace App\Api\User\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthenticatedException extends HttpException
{
    /**
     * @param int $statusCode
     * @param string $message
     * @param \Throwable|null $previous
     * @param array<string, mixed> $headers
     * @param int $code
     */
    protected function __construct(
        int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public static function new(): self
    {
        return new self(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
    }
}
