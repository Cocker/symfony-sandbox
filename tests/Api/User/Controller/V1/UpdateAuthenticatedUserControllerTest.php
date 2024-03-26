<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\User;
use App\Api\User\Fixture\ExistingEmailUserFixture;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\Response;

class UpdateAuthenticatedUserControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;
    private DatabaseToolCollection $databaseToolCollection;
    private Client $client;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->databaseToolCollection = static::getContainer()
            ->get(DatabaseToolCollection::class);

        $this->client = static::createClient();
    }


    public function test_it_returns_401_if_not_authenticated(): void
    {
        $this->client->request(
            'PUT',
            '/api/v1/auth/me',
            ['body' => json_encode(['firstName' => 'Any first name', 'lastName' => 'Any last name'], JSON_THROW_ON_ERROR)]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function ktest_it_returns_validation_errors(): void
    {
        $this->databaseToolCollection->get()->loadFixtures([ExistingEmailUserFixture::class]);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => ExistingEmailUserFixture::EXISTING_EMAIL]);
        $token = $this->getContainer()->get(JWTTokenManagerInterface::class)->create($user);

        $this->client->request(
            'PUT',
            '/api/v1/auth/me',
            [
                'body' => json_encode(['firstName' => 'ab', 'lastName' => str_repeat('a', 256)], JSON_THROW_ON_ERROR),
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_it_updates_the_user(): void
    {
        $this->databaseToolCollection->get()->loadFixtures([ExistingEmailUserFixture::class]);
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => ExistingEmailUserFixture::EXISTING_EMAIL]);
        $token = $this->getContainer()->get(JWTTokenManagerInterface::class)->create($user);

        $this->client->request(
            'PUT',
            '/api/v1/auth/me',
            [
                'body' => json_encode([
                    'firstName' => $firstName = 'John',
                    'lastName' => $lastName = 'Smith',
                ], JSON_THROW_ON_ERROR),
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->clear(); // clear cache

        $updatedUser = $userRepository->findOneBy(['email' => ExistingEmailUserFixture::EXISTING_EMAIL]);

        $this->assertSame($firstName, $updatedUser->getFirstName());
        $this->assertSame($lastName, $updatedUser->getLastName());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager, $this->databaseToolCollection);
    }
}
