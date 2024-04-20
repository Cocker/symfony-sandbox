<?php

declare(strict_types=1);

namespace App\Api\User\Repository\V1;

use App\Api\User\Entity\UserLogin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLogin>
 *
 * @method UserLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserLogin[]    findAll()
 * @method UserLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserLoginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLogin::class);
    }

    public function isSuspiciousLogin(UserLogin $userLogin): bool
    {
        return $this->createQueryBuilder('ul')
            ->where('ul.id <> :currentId')
            ->andWhere('ul.causer = :causerId')
            ->andWhere('ul.ip = :currentIp AND ul.userAgent = :currentUserAgent')
            ->select('COUNT(ul.id)')
            ->setParameter('causerId', $userLogin->getCauser()->getId())
            ->setParameter('currentId', $userLogin->getId())
            ->setParameter('currentIp', $userLogin->getIp())
            ->setParameter('currentUserAgent', $userLogin->getUserAgent())
            ->getQuery()
            ->getSingleScalarResult() === 0
        ;
    }
}
