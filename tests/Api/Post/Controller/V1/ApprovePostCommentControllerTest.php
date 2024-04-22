<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostCommentStatus;
use App\Api\Post\Entity\Factory\PostCommentFactory;
use App\Api\User\Entity\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class ApprovePostCommentControllerTest extends ApiTestCase
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

    public function test_it_returns_404_when_post_comment_does_not_exist(): void
    {
        $randomUlid = new Ulid();
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/post-comments/$randomUlid/approve",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_not_approve_own_post_comment(): void
    {
        $userProxy = UserFactory::createOne();
        $postCommentProxy = PostCommentFactory::new()
            ->withStatus(PostCommentStatus::PENDING)
            ->withAuthor($userProxy->object())
            ->create()
        ;
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/post-comments/{$postCommentProxy->getUlid()}/approve",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $postCommentProxy->refresh();

        $this->assertSame(PostCommentStatus::PENDING, $postCommentProxy->getStatus());
    }

    public function providePostCommentStatuses(): \Generator
    {
        yield [PostCommentStatus::REJECTED];
        yield [PostCommentStatus::APPROVED];
    }

    /**
     * @dataProvider providePostCommentStatuses
     */
    public function test_admin_can_not_approve_if_not_pending(PostCommentStatus $postCommentStatus): void
    {
        $adminUserProxy = UserFactory::new()
            ->admin()
            ->create()
        ;
        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $postCommentProxy = PostCommentFactory::new()
            ->withStatus($postCommentStatus)
            ->create()
        ;

        $this->client->request(
            'POST',
            "/api/v1/post-comments/{$postCommentProxy->getUlid()}/approve",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $postCommentProxy->refresh();

        $this->assertSame($postCommentStatus, $postCommentProxy->getStatus());
    }

    public function test_admin_can_approve_post_comment(): void
    {
        $adminUserProxy = UserFactory::new()
            ->admin()
            ->create()
        ;
        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $postCommentProxy = PostCommentFactory::new()
            ->withStatus(PostCommentStatus::PENDING)
            ->create()
        ;

        $this->client->request(
            'POST',
            "/api/v1/post-comments/{$postCommentProxy->getUlid()}/approve",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $postCommentProxy->refresh();

        $this->assertSame(PostCommentStatus::APPROVED, $postCommentProxy->getStatus());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager);
    }
}

