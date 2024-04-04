<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\Entity\User;
use App\Api\User\Entity\UserLogin;
use App\Api\User\Repository\V1\UserLoginRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserLoginService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MailerInterface $mailer,
    ) {
        //
    }

    public function create(User $user, SignInDTO $signInDTO): UserLogin
    {
        $userLogin = new UserLogin();

        $userLogin
            ->setIp($signInDTO->ip)
            ->setUserAgent($signInDTO->userAgent)
            ->setCauser($user)
        ;

        $this->entityManager->persist($userLogin);
        $this->entityManager->flush();

        return $userLogin;
    }

    public function sendEmailIfSuspiciousLogin(UserLogin $userLogin): void
    {
        $userLoginRepository = $this->entityManager->getRepository(UserLogin::class);

        if (! $userLoginRepository->isSuspiciousLogin($userLogin)) {
            return;
        }

        $email = (new Email())
            ->to($userLogin->getCauser()->getEmail())
            ->subject('Suspicious Login')
            ->text(
                <<<BODY
                Someone is trying to login from a new platform.
                IP: {$userLogin->getIp()}.
                Device: {$userLogin->getUserAgent()}
                BODY
            )
        ;

        $this->mailer->send($email);
    }
}
