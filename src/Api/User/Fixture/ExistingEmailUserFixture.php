<?php

namespace App\Api\User\Fixture;

use App\Api\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ExistingEmailUserFixture extends Fixture
{
    public final const string EXISTING_EMAIL = 'existing@email.com';
    public final const string DEFAULT_PASSWORD = '?@Qwerty123#!';

    public function __construct(protected readonly UserPasswordHasherInterface $userPasswordHasher)
    {
        //
    }

    public function load(ObjectManager $manager): void
    {
        $existingUser = new User();
        $existingUser->setFirstName('First')
            ->setLastName('Last')
            ->setEmail(self::EXISTING_EMAIL)
        ;

        $existingUser->setPassword($this->userPasswordHasher->hashPassword($existingUser, self::DEFAULT_PASSWORD));

        $manager->persist($existingUser);
        $manager->flush();
    }
}