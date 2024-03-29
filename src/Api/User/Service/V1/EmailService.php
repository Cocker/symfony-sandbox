<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\User;
use App\Service\Redis\RedisService;
use App\Service\VerificationCode\Enum\VerificationType;
use App\Service\VerificationCode\Exception\InvalidVerificationCodeException;
use App\Service\VerificationCode\VerificationCodeGeneratorInterface;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        protected readonly VerificationCodeGeneratorInterface $verificationCodeGenerator,
        protected readonly MailerInterface $mailer,
        protected readonly RedisService $redisService,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
    ) {
        //
    }

    public function sendVerificationCode(User $user): void
    {
        if ($user->isEmailVerified()) {
            return;
        }

        $code = $this->verificationCodeGenerator->generate();

        $this->redisService->set(VerificationType::VERIFY_EMAIL->fullKey($user->getUserIdentifier()), $code);

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Verify your email')
            ->text("Your verification code: $code")
        ;

        $this->mailer->send($email);
    }

    public function verify(User $user, string $code): void
    {
        $fullKey = VerificationType::VERIFY_EMAIL->fullKey($user->getUserIdentifier());

        $redisCode = $this->redisService->get($fullKey);
        if ($redisCode !== $code) {
            throw new InvalidVerificationCodeException();
        }

        $user->setStatus(UserStatus::ACTIVE);
        $user->setEmailVerifiedAt(CarbonImmutable::now());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->redisService->delete($fullKey);
    }
}
