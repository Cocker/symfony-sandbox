<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Enum\UserRole;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Entity\User;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;
    private Client $client;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->client = static::createClient();
    }

    public function test_it_creates_user(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $this->client->request('POST','/api/v1/users', [
            'body' => json_encode([
                'firstName' => $firstName = 'First',
                'lastName' => $lastName = 'Last',
                'email' => $email = 'test@mail.com',
                'password' => $password = '?@Qwerty123#!',
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonEquals([
            'status' => UserStatus::ACTIVE->value,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email'=> $email,
            'roles' => [UserRole::USER->value],
            'createdAt' => $now->toIso8601String(),
            'updatedAt' => $now->toIso8601String(),
        ]);

        $userRepository = $this->entityManager->getRepository(User::class);

        /** @var User $createdUser */
        $createdUser = $userRepository->findOneBy(['email' => $email]);

        $this->assertNotNull($createdUser);
        $this->assertSame($firstName, $createdUser->getFirstName());
        $this->assertSame($lastName, $createdUser->getLastName());
        $this->assertSame($email, $createdUser->getEmail());
        $this->assertSame(UserStatus::ACTIVE, $createdUser->getStatus());
        $this->assertSame([UserRole::USER->value], $createdUser->getRoles());
        $this->assertTrue($now->eq($createdUser->getCreatedAt()));
        $this->assertTrue($now->eq($createdUser->getUpdatedAt()));

        $userPasswordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($userPasswordHasher->isPasswordValid($createdUser, $password));
    }

    public function test_it_returns_validation_errors(): void
    {
        $response = $this->client->request('POST','/api/v1/users', [
            'body' => json_encode([
                'firstName' => 'ab', // too short
                'lastName' => str_repeat('a', 256), // too long
                'email' => $email = 'te@st@mail', // invalid email
                'password' => 'qwerty123', // too weak
            ], JSON_THROW_ON_ERROR)
        ]);

        $responseArray = $response->toArray(throw: false);
        $validationErorrs = $responseArray['hydra:description'];

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $userRepository = $this->entityManager->getRepository(User::class);
        $this->assertNull($userRepository->findOneBy(['email' => $email]));

        $this->assertStringContainsString('firstName', $validationErorrs);
        $this->assertStringContainsString('lastName', $validationErorrs);
        $this->assertStringContainsString('email', $validationErorrs);
        $this->assertStringContainsString('password', $validationErorrs);

    }

    public function test_it_returns_error_if_email_already_exists(): void
    {
        $existingUserProxy = UserFactory::createOne();

        $this->client->request('POST','/api/v1/users', [
            'body' => json_encode([
                'firstName' => 'Any last name',
                'lastName' => 'Any last name',
                'email' => $email = $existingUserProxy->getEmail(),
                'password' => '?@Qwerty123#!',
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'email: User with this email already exists.']);

        $userRepository = $this->entityManager->getRepository(User::class);
        $this->assertCount(1, $userRepository->findBy(['email' => $email]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}