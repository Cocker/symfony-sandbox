<?php

namespace App\Api\Post\Repository\V1;

use App\Api\Post\Entity\Post;
use App\Api\Post\Entity\PostComment;
use App\Api\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<PostComment>
 *
 * @method PostComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostComment[]    findAll()
 * @method PostComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostComment::class);
    }

    /**
     * @param Post $post
     * @param int $limit
     * @param int $offset
     * @return Paginator<PostComment>
     */
    public function findByPostPaginated(Post $post, int $limit, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('pc')
            ->where('pc.post = :post')
            ->orderBy('pc.createdAt', 'DESC')
            ->setParameter('post', $post)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        return new Paginator($query);
    }

    /**
     * @param User $user
     * @param int $limit
     * @param int $offset
     * @return Paginator<PostComment>
     */
    public function findByUserPaginated(User $user, int $limit, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('pc')
            ->where('pc.author = :author')
            ->orderBy('pc.createdAt', 'DESC')
            ->setParameter('author', $user)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        return new Paginator($query);
    }

    public function findOneByUlid(string $ulid): ?PostComment
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.ulid = :ulid')
            ->setParameter('ulid', $ulid, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
