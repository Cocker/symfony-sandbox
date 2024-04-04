<?php

declare(strict_types=1);

namespace App\Api\User\EventSubscriber;

use App\Api\User\Entity\UserLogin;
use App\Api\User\Event\UserLoginEvent;
use App\Api\User\Service\V1\UserLoginService;
use App\Api\User\Service\V1\UserService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserLoginEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly UserLoginService $userLoginService,
        protected readonly UserService $userService,
    ) {
        //
    }

    public static function getSubscribedEvents(): array
    {
        return [UserLoginEvent::class => 'onUserLogin'];
    }

    public function onUserLogin(UserLoginEvent $event): void
    {
        $user = $this->userService->findOneBy(['id' => $event->userId]);

        if ($user === null) {
            return;
        }

        $userLogin = $this->userLoginService->create($user, $event->signInDTO);

        $this->userLoginService->sendEmailIfSuspiciousLogin($userLogin);
    }
}
