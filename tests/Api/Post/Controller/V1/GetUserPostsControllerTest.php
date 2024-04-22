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
use Symfony\Component\Uid\Ulid;

class GetUserPostsControllerTest extends ApiTestCase
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

    public function test_it_returns_error_if_user_does_not_exist(): void
    {
        $randomUlid = new Ulid();
        $adminUserProxy = UserFactory::new()->admin()->create();
        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/users/$randomUlid/posts",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_not_access_other_users_posts(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $anotherUserPostProxy = PostFactory::new()
            ->withAuthor($anotherUserProxy->object())
            ->create()
        ;

        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'GET',
            "/api/v1/users/{$anotherUserProxy->getUlid()}/posts",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_get_own_posts(): void
    {
        $userProxy = UserFactory::createOne();
        PostFactory::new()
            ->withAuthor($userProxy->object())
            ->many($count = PostService::RESULTS_PER_PAGE + random_int(1, PostService::RESULTS_PER_PAGE))
            ->create()
        ;
        $token = $this->JWTTokenManager->create($userProxy->object());

        $response = $this->client->request(
            'GET',
            "/api/v1/users/{$userProxy->getUlid()}/posts",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['total' => $count]);

        $this->assertCount(PostService::RESULTS_PER_PAGE, $response->toArray()['data']);

        $response = $this->client->request(
            'GET',
            "/api/v1/users/{$userProxy->getUlid()}/posts",
            [
                'query' => ['page' => 2],
                'headers' => ['Authorization' => "Bearer $token"]
            ]
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
    public function test_user_can_filter_posts_by_status(PostStatus $postStatus): void
    {
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $counts = [
            PostStatus::DRAFT->value => random_int(1, PostService::RESULTS_PER_PAGE),
            PostStatus::PENDING->value => random_int(1, PostService::RESULTS_PER_PAGE),
            PostStatus::REJECTED->value => random_int(1, PostService::RESULTS_PER_PAGE),
            PostStatus::PUBLISHED->value => random_int(1, PostService::RESULTS_PER_PAGE),
        ];

        foreach ($counts as $status => $count) {
            PostFactory::new()
                ->withAuthor($userProxy->object())
                ->withStatus(PostStatus::from($status))
                ->many($count)
                ->create();
        }

        $response = $this->client->request(
            'GET',
            "/api/v1/users/{$userProxy->getUlid()}/posts",
            [
                'query' => ['status' => $postStatus->value],
                'headers' => ['Authorization' => "Bearer $token"]
            ]
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
