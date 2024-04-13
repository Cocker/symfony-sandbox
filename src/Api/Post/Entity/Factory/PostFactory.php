<?php

declare(strict_types=1);

namespace App\Api\Post\Entity\Factory;

use App\Api\Post\Entity\Post;
use App\Api\User\Entity\Factory\UserFactory;
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
    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->sentence(),
            'body' => self::faker()->text(),
            'author' => UserFactory::new(),
        ];
    }
    
    protected static function getClass(): string
    {
        return Post::class;
    }
}
