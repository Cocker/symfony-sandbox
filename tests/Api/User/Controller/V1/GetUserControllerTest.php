<?php

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class GetUserControllerTest extends ApiTestCase
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

    public function test_it_returns_401_if_not_authenticated(): void
    {
        $randomUlid = new Ulid();

        $this->client->request('GET',"/api/v1/users/$randomUlid");
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_user_if_authenticated(): void
    {
        $userProxy = UserFactory::createOne();

        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/users/{$userProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['email' => $userProxy->getEmail()]);
    }

    public function test_user_can_not_access_other_users(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();

        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/users/{$anotherUserProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_admin_can_access_any_user(): void
    {
        $userProxy = UserFactory::createOne();
        $adminUserProxy = UserFactory::new()->admin()->create();

        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($adminUserProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/users/{$userProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['ulid' => (string) $userProxy->getUlid()]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
