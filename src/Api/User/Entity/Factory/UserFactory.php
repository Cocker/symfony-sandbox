<?php

namespace App\Api\User\Entity\Factory;

use App\Api\User\Entity\Enum\UserRole;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\User;
use App\Api\User\Repository\V1\UserRepository;
use Carbon\CarbonImmutable;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<User>
 *
 * @method        User|Proxy                     create(array|callable $attributes = [])
 * @method static User|Proxy                     createOne(array $attributes = [])
 * @method static User|Proxy                     find(object|array|mixed $criteria)
 * @method static User|Proxy                     findOrCreate(array $attributes)
 * @method static User|Proxy                     first(string $sortedField = 'id')
 * @method static User|Proxy                     last(string $sortedField = 'id')
 * @method static User|Proxy                     random(array $attributes = [])
 * @method static User|Proxy                     randomOrCreate(array $attributes = [])
 * @method static UserRepository|RepositoryProxy repository()
 * @method static User[]|Proxy[]                 all()
 * @method static User[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static User[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static User[]|Proxy[]                 findBy(array $attributes)
 * @method static User[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static User[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    public function withPassword(#[\SensitiveParameter] string $plainPassword): UserFactory
    {
        return $this->addState(['plainPassword' => $plainPassword]);
    }

    public function unverified(): UserFactory
    {
        return $this->addState([
            'status' => UserStatus::UNVERIFIED,
            'email_verified_at' => null,
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'email' => self::faker()->safeEmail(),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'plainPassword' => self::faker()->password(minLength:  8),
            'roles' => [UserRole::USER->value],
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => CarbonImmutable::now()->subDay(),
        ];
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
