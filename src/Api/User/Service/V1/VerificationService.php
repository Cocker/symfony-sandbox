<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use App\Api\User\Entity\User;
use App\Api\User\Exception\InvalidVerificationCodeException;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\Shared\VerificationCodeGenerator\VerificationCodeGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;

class VerificationService
{
    public function __construct(
        protected readonly CacheItemPoolInterface $verificationPool,
        protected readonly VerificationCodeGeneratorInterface $verificationCodeGenerator,
    ) {
        //
    }

    public function new(VerificationType $verificationType, User $user): string
    {
        $code = $this->verificationCodeGenerator->generate();
        $fullKey = $verificationType->fullKey($user);

        $cacheItem = $this->verificationPool->getItem($fullKey);
        $cacheItem->expiresAfter($verificationType->ttlSeconds());
        $cacheItem->set($code);

        $this->verificationPool->save($cacheItem);

        return $code;
    }

    public function getCode(VerificationType $verificationType, User $user): ?string
    {
        $fullKey = $verificationType->fullKey($user);

        $cacheItem = $this->verificationPool->getItem($fullKey);

        if (! $cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->get();
    }

    public function ensureIsValid(
        VerificationType $verificationType,
        User $user,
        string $code,
        bool $throw = true
    ): bool {
        $validCode = $this->getCode($verificationType, $user);

        $isValid = $validCode !== null && $validCode === $code;

        if ($throw && ! $isValid) {
            throw new InvalidVerificationCodeException();
        }

        return $isValid;
    }

    public function delete(VerificationType $verificationType, User $user): void
    {
        $this->verificationPool->deleteItem($verificationType->fullKey($user));
    }
}
