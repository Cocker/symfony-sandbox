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

class RejectPostControllerTest extends ApiTestCase
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

        $this->client->request('POST', "/api/v1/posts/$randomUlid/reject");

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_404_when_post_does_not_exist(): void
    {
        $randomUlid = new Ulid();
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/{$randomUlid}/reject",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_not_reject_own_post(): void
    {
        $userProxy = UserFactory::createOne();
        $randomUlid = new Ulid();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/$randomUlid/reject",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function providePostStatuses(): \Generator
    {
        yield [PostStatus::DRAFT];
        yield [PostStatus::REJECTED];
        yield [PostStatus::PUBLISHED];
    }

    /**
     * @dataProvider providePostStatuses
     */
    public function test_admin_can_not_reject_post_if_not_pending(PostStatus $postStatus): void
    {
        $userProxy = UserFactory::createOne();
        $adminUserProxy = UserFactory::new()->admin()->create();
        $postProxy = PostFactory::new()
            ->withStatus($postStatus)
            ->withAuthor($userProxy->object())
            ->create()
        ;

        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/reject",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $postProxy->refresh();

        $this->assertSame($postStatus, $postProxy->getStatus());
    }

    public function test_admin_can_reject_post(): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withStatus(PostStatus::PENDING)
            ->withAuthor($userProxy->object())
            ->create()
        ;

        $adminUserProxy = UserFactory::new()->admin()->create();

        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/reject",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $postProxy->refresh();

        $this->assertSame(PostStatus::REJECTED, $postProxy->getStatus());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager);
    }
}
