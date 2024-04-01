<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Command;

use App\Api\User\Command\PruneUsersCommand;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Entity\User;
use App\Api\User\Repository\V1\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Proxy;

class PruneUsersCommandTest extends KernelTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function test_it_should_not_prune_inactive_users_if_dry_run(): void
    {
        $inactiveUsers = UserFactory::new()
            ->createdAtLeastMoreThanDaysAgo(UserRepository::DAYS_BEFORE_USER_CAN_BE_PRUNED)
            ->unverified()
            ->many($inactiveUsersCount = random_int(1, 10))
            ->create()
        ;

        $activeUsers = UserFactory::new()
            ->many($activeUsersCount = random_int(1, 10))
            ->create()
        ;

        $command = static::getContainer()->get(PruneUsersCommand::class);
        $commandTester = new CommandTester($command);


        $commandTester->execute(['--dry-run' => true]);
        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $userRepository = $this->entityManager->getRepository(User::class);

        $this->assertCount($inactiveUsersCount + $activeUsersCount, $userRepository->findAll());

        $this->assertStringContainsString("Deleted $inactiveUsersCount users", $display);
        $this->assertStringContainsString('Dry run enabled', $display);
    }

    public function test_it_should_prune_inactive_users(): void
    {
        $inactiveUsers = UserFactory::new()
            ->createdAtLeastMoreThanDaysAgo(UserRepository::DAYS_BEFORE_USER_CAN_BE_PRUNED)
            ->unverified()
            ->many($inactiveUsersCount = random_int(1, 10))
            ->create()
        ;

        $activeUsers = UserFactory::new()
            ->many($activeUsersCount = random_int(1, 10))
            ->create()
        ;

        $command = static::getContainer()->get(PruneUsersCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $userRepository = $this->entityManager->getRepository(User::class);

        $this->assertEmpty($userRepository->findBy(
            ['id' => array_map(fn (Proxy|User $user) => $user->getId(), $inactiveUsers)],
        ));

        $this->assertCount($activeUsersCount, $userRepository->findBy(
            ['id' => array_map(fn (Proxy|User $user) => $user->getId(), $activeUsers)],
        ));

        $this->assertStringContainsString("Deleted $inactiveUsersCount users", $display);
        $this->assertStringNotContainsString('Dry run enabled', $display);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
