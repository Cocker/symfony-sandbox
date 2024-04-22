<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Factory\PostCommentFactory;
use App\Api\Post\Entity\Factory\PostFactory;
use App\Api\Post\Service\V1\PostCommentService;
use App\Api\User\Entity\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class GetPostCommentsByPostControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    public function test_it_returns_error_if_post_does_not_exist(): void
    {
        $randomUlid = new Ulid();
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/posts/$randomUlid/comments",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_get_comments(): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withStatus(PostStatus::PUBLISHED)
            ->withAuthor($userProxy->object())
            ->create()
        ;
        $postCommentProxies = PostCommentFactory::new()
            ->withAuthor($userProxy->object())
            ->withPost($postProxy->object())
            ->many($count = PostCommentService::RESULTS_PER_PAGE + random_int(1, PostCommentService::RESULTS_PER_PAGE))
            ->create()
        ;
        $token = $this->JWTTokenManager->create($userProxy->object());

        $response = $this->client->request(
            'GET',
            "/api/v1/posts/{$postProxy->getUlid()}/comments",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['total' => $count]);
        $this->assertCount(PostCommentService::RESULTS_PER_PAGE, $response->toArray()['data']);

        $response = $this->client->request(
            'GET',
            "/api/v1/posts/{$postProxy->getUlid()}/comments",
            [
                'headers' => ['Authorization' => "Bearer $token"],
                'query' => ['page' => 2]
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['total' => $count]);

        $this->assertCount($count - PostCommentService::RESULTS_PER_PAGE, $response->toArray()['data']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager);
    }
}

