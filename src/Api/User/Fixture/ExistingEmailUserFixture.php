<?php

namespace App\Api\User\Fixture;

use App\Api\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExistingEmailUserFixture extends Fixture
{
    public final const string EXISTING_EMAIL = 'existing@email.com';

    public function load(ObjectManager $manager)
    {
        $existingUser = new User();
        $existingUser->setFirstName('First')
            ->setLastName('Last')
            ->setEmail(self::EXISTING_EMAIL)
            ->setPassword('Qwerty123');

        $manager->persist($existingUser);
        $manager->flush();
    }
}