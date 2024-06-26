<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Enum\UserRole;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Entity\User;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\Shared\VerificationCodeGenerator\StaticVerificationCodeGenerator;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Ulid;

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

        $verificationPool = static::getContainer()->get('verification_pool');

        $this->client->request('POST','/api/v1/users', [
            'json' => [
                'firstName' => $firstName = 'First',
                'lastName' => $lastName = 'Last',
                'email' => $email = 'test@mail.com',
                'password' => $password = '?@Qwerty123#!',
            ],
        ]);

        $userRepository = $this->entityManager->getRepository(User::class);

        /** @var User $createdUser */
        $createdUser = $userRepository->findOneBy(['email' => $email]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonEquals([
            'ulid' => $createdUser->getUlid(),
            'status' => UserStatus::UNVERIFIED->value,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email'=> $email,
            'roles' => [UserRole::USER->value],
            'createdAt' => $now->toIso8601String(),
            'updatedAt' => $now->toIso8601String(),
            'emailVerifiedAt' => null,
        ]);

        $this->assertNotNull($createdUser);
        $this->assertSame($firstName, $createdUser->getFirstName());
        $this->assertSame($lastName, $createdUser->getLastName());
        $this->assertSame($email, $createdUser->getEmail());
        $this->assertSame(UserStatus::UNVERIFIED, $createdUser->getStatus());
        $this->assertSame([UserRole::USER->value], $createdUser->getRoles());
        $this->assertTrue($now->eq($createdUser->getCreatedAt()));
        $this->assertTrue($now->eq($createdUser->getUpdatedAt()));

        $this->assertTrue(Ulid::isValid((string) $createdUser->getUlid()));

        $userPasswordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($userPasswordHasher->isPasswordValid($createdUser, $password));

        $this->assertQueuedEmailCount(1);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        $this->assertEquals('Verify your email', $email->getSubject());
        $this->assertStringContainsString(StaticVerificationCodeGenerator::CODE, $email->getTextBody());

        $codeCacheItem = $verificationPool->getItem(VerificationType::EMAIL_VERIFY->fullKey($createdUser));
        $this->assertTrue($codeCacheItem->isHit());
        $this->assertEquals(StaticVerificationCodeGenerator::CODE, $codeCacheItem->get());
    }

    public function test_it_returns_validation_errors(): void
    {
        $response = $this->client->request('POST','/api/v1/users', [
            'json' => [
                'firstName' => 'ab', // too short
                'lastName' => str_repeat('a', 256), // too long
                'email' => $email = 'te@st@mail', // invalid email
                'password' => 'qwerty123', // too weak
            ],
        ]);

        $responseArray = $response->toArray(throw: false);
        $validationErrors = $responseArray['hydra:description'];

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $userRepository = $this->entityManager->getRepository(User::class);
        $this->assertNull($userRepository->findOneBy(['email' => $email]));

        $this->assertStringContainsString('firstName', $validationErrors);
        $this->assertStringContainsString('lastName', $validationErrors);
        $this->assertStringContainsString('email', $validationErrors);
        $this->assertStringContainsString('password', $validationErrors);
    }

    public function test_it_returns_error_if_email_already_exists(): void
    {
        $existingUserProxy = UserFactory::createOne();

        $this->client->request('POST','/api/v1/users', [
            'json' => [
                'firstName' => 'Any last name',
                'lastName' => 'Any last name',
                'email' => $email = $existingUserProxy->getEmail(),
                'password' => '?@Qwerty123#!',
            ],
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
        unset($this->entityManager, $this->client);
    }
}