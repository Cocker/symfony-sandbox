<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Factory\PostFactory;
use App\Api\Post\Service\V1\PostService;
use App\Api\User\Entity\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class GetPostsControllerTest extends ApiTestCase
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
        $this->client->request('GET', '/api/v1/posts');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_user_can_not_access_all_posts(): void
    {
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'GET',
            '/api/v1/posts',
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_access_all_posts(): void
    {
        $adminUserProxy = UserFactory::new()->admin()->create();
        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        PostFactory::new()
            ->withRandomStatus()
            ->many($count = PostService::RESULTS_PER_PAGE + random_int(1, PostService::RESULTS_PER_PAGE))
            ->create();

        $response = $this->client->request(
            'GET',
            '/api/v1/posts',
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['total' => $count]);

        $this->assertCount(PostService::RESULTS_PER_PAGE, $response->toArray()['data']);

        $response = $this->client->request(
            'GET',
            '/api/v1/posts',
            [
                'query' => ['page' => 2],
                'headers' => ['Authorization' => "Bearer $token"]
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['total' => $count]);

        $this->assertCount($count - PostService::RESULTS_PER_PAGE, $response->toArray()['data']);
    }

    public function providePostStatuses(): \Generator
    {
        yield [PostStatus::DRAFT];
        yield [PostStatus::PENDING];
        yield [PostStatus::REJECTED];
        yield [PostStatus::PUBLISHED];
    }

    /**
     * @dataProvider providePostStatuses
     */
    public function test_admin_can_filter_posts_by_status(PostStatus $postStatus): void
    {
        $adminUserProxy = UserFactory::new()->admin()->create();
        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $counts = [
            PostStatus::DRAFT->value => random_int(1, PostService::RESULTS_PER_PAGE),
            PostStatus::PENDING->value => random_int(1, PostService::RESULTS_PER_PAGE),
            PostStatus::REJECTED->value => random_int(1, PostService::RESULTS_PER_PAGE),
            PostStatus::PUBLISHED->value => random_int(1, PostService::RESULTS_PER_PAGE),
        ];

        foreach ($counts as $status => $count) {
            PostFactory::new()
                ->withStatus(PostStatus::from($status))
                ->many($count)
                ->create();
        }

        $response = $this->client->request(
            'GET',
            '/api/v1/posts',
            [
                'query' => ['status' => $postStatus->value],
                'headers' => ['Authorization' => "Bearer $token"]
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['total' => $counts[$postStatus->value]]);
        $this->assertCount($counts[$postStatus->value], $response->toArray()['data']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager);
    }
}

