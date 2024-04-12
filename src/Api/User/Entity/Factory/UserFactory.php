<?php

declare(strict_types=1);

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

    public function withEmail(string $email): UserFactory
    {
        return $this->addState(['email' => $email]);
    }

    public function unverified(): UserFactory
    {
        return $this->addState([
            'status' => UserStatus::UNVERIFIED,
            'emailVerifiedAt' => null,
        ]);
    }

    public function admin(): UserFactory
    {
        return $this
            ->withEmail('admin@mail.com')
            ->withPassword('!#$Qwerty123^&*')
            ->addState([
                'firstName' => 'Admin',
                'lastName' => 'Admin',
                'status' => UserStatus::ACTIVE,
                'roles' => [UserRole::ADMIN->value],
            ])
        ;
    }

    public function createdAtLeastMoreThanDaysAgo(int $days): UserFactory
    {
        $minutes = random_int(1, 24 * 60);

        return $this->addState([
            'createdAt' => (new \DateTimeImmutable(-$days . ' days'))
                ->modify(-$minutes . ' minutes'),
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
            'emailVerifiedAt' => CarbonImmutable::now()->subDay(),
        ];
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
