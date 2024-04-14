<?php

declare(strict_types=1);

namespace App\Api\Post\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostNotPendingException extends HttpException
{
    protected function __construct(
        int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public static function new(): static
    {
        return new static(Response::HTTP_BAD_REQUEST, 'Post is not in pending status');
    }
}