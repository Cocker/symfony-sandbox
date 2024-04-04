<?php

namespace App\Api\User\Repository\V1;

use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public const int DAYS_BEFORE_USER_CAN_BE_PRUNED = 30;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // set the new encoded password on the User object
        $user->setPassword($newHashedPassword);

        // execute the queries on the database
        $this->getEntityManager()->flush();
    }

    public function getPrunableUsersQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.emailVerifiedAt IS NULL')
            ->andWhere('u.createdAt < :date')
            ->setParameter('status', UserStatus::UNVERIFIED)
            ->setParameter(
                'date',
                new \DateTimeImmutable(-self::DAYS_BEFORE_USER_CAN_BE_PRUNED . ' days'),
                Types::DATETIME_IMMUTABLE,
            )
        ;
    }

    public function countPrunableUsers(): int
    {
        return $this->getPrunableUsersQueryBuilder()->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
    }

    public function pruneUsers(): int
    {
        return $this->getPrunableUsersQueryBuilder()->delete()->getQuery()->execute();
    }
}