<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class UpdateAuthenticatedUserControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;

    protected function setUp(): void
    {
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

    public function test_it_returns_validation_errors(): void
    {
        $userProxy = UserFactory::createOne();

        $token = $this->getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

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
        $userProxy = UserFactory::createOne();
        $token = $this->getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

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

        $userProxy->refresh();

        $this->assertSame($firstName, $userProxy->getFirstName());
        $this->assertSame($lastName, $userProxy->getLastName());
    }
}
