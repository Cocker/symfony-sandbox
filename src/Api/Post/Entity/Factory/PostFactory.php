<?php

declare(strict_types=1);

namespace App\Api\Post\Entity\Factory;

use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Entity\User;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Post>
 *
 * @method        Post|Proxy     create(array|callable $attributes = [])
 * @method static Post|Proxy     createOne(array $attributes = [])
 * @method static Post[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Post[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class PostFactory extends ModelFactory
{
    public function withAuthor(User $user): PostFactory
    {
        return $this->addState(['author' => $user]);
    }

    public function withStatus(PostStatus $status): PostFactory
    {
        return $this->addState(['status' => $status]);
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->sentence(),
            'body' => self::faker()->realTextBetween(300, 1000),
            'author' => UserFactory::new(),
        ];
    }
    
    protected static function getClass(): string
    {
        return Post::class;
    }
}
