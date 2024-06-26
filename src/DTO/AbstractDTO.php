<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

abstract readonly class AbstractDTO
{
    protected function __construct() {}

    abstract public static function fromRequest(Request $request): self;

    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    protected static function requestContentToArray(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
