<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Factory\PostFactory;
use App\Api\User\Entity\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class GetPostControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    public function test_it_returns_error_if_unathenticated(): void
    {
        $randomUlid = new Ulid();

        $this->client->request('GET', "/api/v1/posts/$randomUlid");

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_404_when_post_does_not_exist(): void
    {
        $userProxy = UserFactory::createOne();
        $randomUlid = new Ulid();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/posts/{$randomUlid}",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_not_get_other_users_posts(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $anotherUserPostProxy = PostFactory::new()
            ->withAuthor($anotherUserProxy->object())
            ->create();

        $this->client->request(
            'GET',
            "/posts/{$anotherUserPostProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_get_post(): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withAuthor($userProxy->object())
            ->create();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/posts/{$postProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonEquals([
            'ulid' => (string) $postProxy->getUlid(),
            'status' => PostStatus::DRAFT->value,
            'title' => $postProxy->getTitle(),
            'body' => $postProxy->getBody(),
            'createdAt' => $postProxy->getCreatedAt()->toIso8601String(),
            'updatedAt' => $postProxy->getUpdatedAt()->toIso8601String(),
            'publishedAt' => null,
        ]);
    }

    public function test_admin_can_get_other_users_posts(): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withAuthor($userProxy->object())
            ->create();

        $adminUserProxy = UserFactory::new()->admin()->create();
        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/posts/{$postProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonEquals([
            'ulid' => (string) $postProxy->getUlid(),
            'status' => PostStatus::DRAFT->value,
            'title' => $postProxy->getTitle(),
            'body' => $postProxy->getBody(),
            'createdAt' => $postProxy->getCreatedAt()->toIso8601String(),
            'updatedAt' => $postProxy->getUpdatedAt()->toIso8601String(),
            'publishedAt' => null,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager);
    }
}
