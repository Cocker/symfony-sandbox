<?php

declare(strict_types=1);

namespace App\Api\User\Entity\EventListener;

use App\Api\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserEventListener
{
    public function __construct(protected readonly UserPasswordHasherInterface $userPasswordHasher)
    {
        //
    }

    public function prePersist(User $user): void
    {
        $this->hashPasswordIfNeeded($user);
    }

    public function preUpdate(User $user): void
    {
        $this->hashPasswordIfNeeded($user);
    }

    private function hashPasswordIfNeeded(User $user): void
    {
        if ($user->getPlainPassword() === null) {
            return;
        }

        $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPlainPassword()));
    }
}
