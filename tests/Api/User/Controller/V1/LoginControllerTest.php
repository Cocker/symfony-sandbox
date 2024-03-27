<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends ApiTestCase
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

    public function test_user_can_login(): void
    {
        $userProxy = UserFactory::new()
            ->withPassword($plainPassword = '!@#Qwerty123$%^')
            ->create();

        $response = $this->client->request('POST','/api/v1/auth/login', [
            'body' => json_encode([
                'email' => $userProxy->getEmail(),
                'password' => $plainPassword,
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertArrayHasKey('token', $json);

        $this->client->request('GET','/api/v1/auth/me');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->client->request(
            'GET',
            '/api/v1/auth/me',
            ['headers' => ['Authorization' => sprintf("Bearer {$json['token']}")]]
        );
        $this->assertResponseIsSuccessful();
    }

    public function test_it_returns_401_if_invalid_credentials_are_provided(): void
    {
        $user = UserFactory::new()
            ->withPassword('!@#Qwerty123$%^')
            ->create();

        $this->client->request('POST','/api/v1/auth/login', [
            'body' => json_encode([
                'email' => 'invalid',
                'password' => 'invalid',
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
