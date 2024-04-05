<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use App\Api\User\DTO\V1\VerifyEmailUpdateDTO;
use App\Api\User\Entity\User;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailUpdateService
{
    public function __construct(
        protected readonly MailerInterface $mailer,
        protected readonly EntityManagerInterface $entityManager,
    ) {
        //
    }

    public function sendRequestUpdateEmail(User $user, string $code): void
    {
        $ttlSeconds = VerificationType::EMAIL_UPDATE->ttlSeconds();

        $verificationEmail = (new Email())
            ->to($newEmail = $user->getNewEmail())
            ->subject('Verify your new email')
            ->text(
                <<<BODY
                Your verification code: $code
                New email: $newEmail
                Active for: $ttlSeconds seconds
                BODY
            )
        ;

        $notificationEmail = (new Email())
            ->to($user->getEmail())
            ->subject('Security notification')
            ->text(
                <<<BODY
                Someone requested email update for your account.
                New email: $newEmail
                BODY
            )
        ;

        $this->mailer->send($verificationEmail);
        $this->mailer->send($notificationEmail);
    }

    public function verify(User $user, VerifyEmailUpdateDTO $verifyEmailUpdateDTO): User
    {
        $user->setEmail($verifyEmailUpdateDTO->newEmail);
        $user->setEmailVerifiedAt(CarbonImmutable::now());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
