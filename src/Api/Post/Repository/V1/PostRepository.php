<?php

declare(strict_types=1);

namespace App\Api\Post\Repository\V1;

use App\Api\Post\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findOneByUlid(string $ulid): ?Post
    {
        return $this->createQueryBuilder('u')
            ->where('u.ulid = :ulid')
            ->setParameter('ulid', $ulid, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
