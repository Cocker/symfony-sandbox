<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\UpdatePasswordDTO;
use App\Api\User\Entity\User;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
        protected readonly MailerInterface $mailer,
    ) {
        //
    }

    public function update(User $user, #[\SensitiveParameter] string $newPassword): User
    {
        $user->setPlainPassword($newPassword);

        $this->validator->validate($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function sendResetPasswordEmail(User $user, string $code): void
    {
        $ttlSeconds = VerificationType::PASSWORD_RESET->ttlSeconds();

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Reset password')
            ->text(
                <<<BODY
                Your verification code: $code
                Active for: $ttlSeconds seconds
                BODY
            )
        ;

        $this->mailer->send($email);
    }
}
