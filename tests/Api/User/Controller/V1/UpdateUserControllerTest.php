<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class UpdateUserControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }


    public function test_it_returns_401_if_not_authenticated(): void
    {
        $randomUlid = new Ulid();

        $this->client->request(
            'PUT',
            "/api/v1/users/$randomUlid",
            ['json' => ['firstName' => 'Any first name', 'lastName' => 'Any last name']]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_validation_errors(): void
    {
        $userProxy = UserFactory::createOne();

        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

        $this->client->request(
            'PUT',
            "/api/v1/users/{$userProxy->getUlid()}",
            [
                'json' => [
                    'firstName' => 'ab',
                    'lastName' => str_repeat('a', 256),
                ],
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_user_can_not_update_other_user(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

        $this->client->request(
            'PUT',
            "/api/v1/users/{$anotherUserProxy->getUlid()}",
            [
                'json' => [
                    'firstName' => 'Any first name',
                    'lastName' => 'Any last name',
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_updates_the_user(): void
    {
        $userProxy = UserFactory::createOne();
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($userProxy->object());

        $this->client->request(
            'PUT',
            "/api/v1/users/{$userProxy->getUlid()}",
            [
                'json' => [
                    'firstName' => $firstName = 'John',
                    'lastName' => $lastName = 'Smith',
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $userProxy->refresh();

        $this->assertSame($firstName, $userProxy->getFirstName());
        $this->assertSame($lastName, $userProxy->getLastName());
    }

    public function test_admin_can_update_any_user(): void
    {
        $userProxy = UserFactory::createOne();
        $adminUserProxy = UserFactory::new()->admin()->create();
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($adminUserProxy->object());

        $this->client->request(
            'PUT',
            "/api/v1/users/{$userProxy->getUlid()}",
            [
                'json' => [
                    'firstName' => $firstName = 'John',
                    'lastName' => $lastName = 'Smith',
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $userProxy->refresh();

        $this->assertSame($firstName, $userProxy->getFirstName());
        $this->assertSame($lastName, $userProxy->getLastName());
    }
}
