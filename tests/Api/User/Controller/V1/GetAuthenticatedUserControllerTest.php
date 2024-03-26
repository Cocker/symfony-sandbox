<?php

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Enum\UserRole;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\User;
use App\Api\User\Fixture\ExistingEmailUserFixture;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\Response;

class GetAuthenticatedUserControllerTest extends ApiTestCase
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
        $this->client->request('GET','/api/v1/auth/me');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_user_if_authenticated(): void
    {
        $this->databaseToolCollection->get()->loadFixtures([ExistingEmailUserFixture::class]);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => ExistingEmailUserFixture::EXISTING_EMAIL]);
        $token = $this->getContainer()->get(JWTTokenManagerInterface::class)->create($user);

        $this->client->request(
            'GET',
            '/api/v1/auth/me',
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['email' => ExistingEmailUserFixture::EXISTING_EMAIL]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager, $this->databaseToolCollection);
    }
}
