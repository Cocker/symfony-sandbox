<?php

namespace App\DataFixtures;

use App\Api\User\Entity\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public const string ADMIN_USER_REFERENCE = 'admin-user';

    public function load(ObjectManager $manager): void
    {
        $adminUser = UserFactory::new()
            ->admin()
            ->withoutPersisting()
            ->create()
        ;

        $manager->persist($adminUser->object());
        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser->object());
    }
}
