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

class CompletePostControllerTest extends ApiTestCase
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

    public function test_it_returns_error_if_unauthenticated(): void
    {
        $randomUlid = new Ulid();

        $this->client->request('POST', "/api/v1/posts/$randomUlid/complete");

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_404_when_post_does_not_exist(): void
    {
        $userProxy = UserFactory::createOne();
        $randomUlid = new Ulid();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/{$randomUlid}/complete",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_not_complete_other_users_post(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());
        $anotherUserPostProxy = PostFactory::new()
            ->withAuthor($anotherUserProxy->object())
            ->create();

        $this->client->request(
            'POST',
            "/api/v1/posts/{$anotherUserPostProxy->getUlid()}/complete",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $anotherUserPostProxy->refresh();

        $this->assertSame(PostStatus::DRAFT, $anotherUserPostProxy->getStatus());
    }

    public function providePostStatuses(): \Generator
    {
        yield [PostStatus::PENDING];
        yield [PostStatus::REJECTED];
        yield [PostStatus::PUBLISHED];
    }

    /**
     * @dataProvider providePostStatuses
     */
    public function test_user_can_not_complete_post_if_not_draft(PostStatus $postStatus): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withAuthor($userProxy->object())
            ->withStatus($postStatus)
            ->create()
        ;
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/complete",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $postProxy->refresh();

        $this->assertSame($postStatus, $postProxy->getStatus());
    }

    public function test_user_can_complete_own_post(): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withAuthor($userProxy->object())
            ->create();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/complete",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $postProxy->refresh();

        $this->assertSame(PostStatus::PENDING, $postProxy->getStatus());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager);
    }
}
