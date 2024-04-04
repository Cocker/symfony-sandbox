<?php

namespace App\Api\User\Entity\Factory;

use App\Api\User\Entity\User;
use App\Api\User\Entity\UserLogin;
use App\Api\User\Repository\V1\UserLoginRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<UserLogin>
 *
 * @method        UserLogin|Proxy                     create(array|callable $attributes = [])
 * @method static UserLogin|Proxy                     createOne(array $attributes = [])
 * @method static UserLogin|Proxy                     find(object|array|mixed $criteria)
 * @method static UserLogin|Proxy                     findOrCreate(array $attributes)
 * @method static UserLogin|Proxy                     first(string $sortedField = 'id')
 * @method static UserLogin|Proxy                     last(string $sortedField = 'id')
 * @method static UserLogin|Proxy                     random(array $attributes = [])
 * @method static UserLogin|Proxy                     randomOrCreate(array $attributes = [])
 * @method static UserLoginRepository|RepositoryProxy repository()
 * @method static UserLogin[]|Proxy[]                 all()
 * @method static UserLogin[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static UserLogin[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static UserLogin[]|Proxy[]                 findBy(array $attributes)
 * @method static UserLogin[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static UserLogin[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class UserLoginFactory extends ModelFactory
{
    public function withIp(string $ip): UserLoginFactory
    {
        return $this->addState(['ip' => $ip]);
    }

    public function withUserAgent(string $userAgent): UserLoginFactory
    {
        return $this->addState(['userAgent' => $userAgent]);
    }

    public function withCauser(User $user): UserLoginFactory
    {
        return $this->addState(['causer' => $user]);
    }

    protected function getDefaults(): array
    {
        return [
            'causer' => UserFactory::new(),
            'ip' => self::faker()->ipv4(),
            'userAgent' => self::faker()->userAgent(),
        ];
    }

    protected static function getClass(): string
    {
        return UserLogin::class;
    }
}
