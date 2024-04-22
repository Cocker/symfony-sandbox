<?php

declare(strict_types=1);

namespace App\Api\Post\Entity\Factory;

use App\Api\Post\Entity\Enum\PostCommentStatus;
use App\Api\Post\Entity\Post;
use App\Api\Post\Entity\PostComment;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Entity\User;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<PostComment>
 *
 * @method        PostComment|Proxy     create(array|callable $attributes = [])
 * @method static PostComment|Proxy     createOne(array $attributes = [])
 * @method static PostComment[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static PostComment[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class PostCommentFactory extends ModelFactory
{
    public function withStatus(PostCommentStatus $status): PostCommentFactory
    {
        return $this->addState(['status' => $status]);
    }

    public function withAuthor(User $author): PostCommentFactory
    {
        return $this->addState(['author' => $author]);
    }

    public function withPost(Post $post): PostCommentFactory
    {
        return $this->addState(['post' => $post]);
    }

    protected function getDefaults(): array
    {
        return [
            'status' => PostCommentStatus::APPROVED,
            'content' => self::faker()->text(300),
            'author' => UserFactory::new(),
            'post' => PostFactory::new(),
        ];
    }

    protected static function getClass(): string
    {
        return PostComment::class;
    }
}
