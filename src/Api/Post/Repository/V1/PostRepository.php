<?php

declare(strict_types=1);

namespace App\Api\Post\Repository\V1;

use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    /**
     * @param int $limit
     * @param int $offset
     * @param PostStatus|null $postStatus
     * @return Paginator<Post>
     */
    public function findAllPaginated(int $limit, int $offset, ?PostStatus $postStatus = null): Paginator
    {
        $query = $this->getPaginatedQuery($limit, $offset, $postStatus);

        return new Paginator($query);
    }

    /**
     * @param User $user
     * @param int $limit
     * @param int $offset
     * @param PostStatus|null $postStatus
     * @return Paginator<Post>
     */
    public function findByUserPaginated(User $user, int $limit, int $offset, ?PostStatus $postStatus): Paginator
    {
        $query = $this->getPaginatedQuery($limit, $offset, $postStatus);

        $query->andWhere('p.author = :authorId')
            ->setParameter('authorId', $user->getId())
        ;

        return new Paginator($query);
    }

    protected function getPaginatedQuery(int $limit, int $offset, ?PostStatus $postStatus): QueryBuilder
    {
        $query = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        if ($postStatus !== null) {
            $query->andWhere('p.status = :status')
                ->setParameter('status', $postStatus)
            ;
        }

        return $query;
    }
}
