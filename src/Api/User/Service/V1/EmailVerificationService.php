<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\User;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailVerificationService
{
    public function __construct(
        protected readonly MailerInterface $mailer,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
    ) {
        //
    }

    public function sendVerificationCode(User $user, string $code): void
    {
        $ttlSeconds = VerificationType::EMAIL_VERIFY->ttlSeconds();

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Verify your email')
            ->text(
                <<<BODY
                Your verification code: $code
                Active for: $ttlSeconds seconds
                BODY
            )
        ;

        $this->mailer->send($email);
    }

    public function verify(User $user): void
    {
        $user->setStatus(UserStatus::ACTIVE);
        $user->setEmailVerifiedAt(CarbonImmutable::now());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
