<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\User\Entity\Factory\UserFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class CreatePostControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;
    private EntityManagerInterface $entityManager;
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->faker = Factory::create();
    }

    public function test_it_returns_validation_errors(): void
    {
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $response = $this->client->request(
            'POST',
            "/api/v1/users/{$userProxy->getUlid()}/posts",
            [
                'json' => [
                    'title' => str_repeat('a', 256),
                    'body' => 'short',
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $responseArray = $response->toArray(throw: false);
        $validationErrors = $responseArray['hydra:description'];

        $this->assertStringContainsString('title', $validationErrors);
        $this->assertStringContainsString('body', $validationErrors);
    }

    public function test_user_can_not_create_post_for_other_user(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());
        $postRepository = $this->entityManager->getRepository(Post::class);

        $this->client->request(
            'POST',
            "/api/v1/users/{$anotherUserProxy->getUlid()}/posts",
            [
                'json' => [
                    'title' => $title = 'Some title',
                    'body' => $body = 'Some body of the post'
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertNull($postRepository->findOneBy(['title' => $title, 'body' => $body]));
    }

    public function test_user_can_create_post(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());
        $postRepository = $this->entityManager->getRepository(Post::class);

        $this->client->request(
            'POST',
            "/api/v1/users/{$userProxy->getUlid()}/posts",
            [
                'json' => [
                    'title' => $title = $this->faker->sentence(),
                    'body' => $body = $this->faker->realTextBetween(300, 1000)
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $createdPost = $postRepository->findOneBy([
            'status' => PostStatus::DRAFT->value,
            'author' => $userProxy->getId(),
            'title' => $title,
            'body' => $body,
        ]);

        $this->assertNotNull($createdPost);

        $this->assertJsonEquals([
            'ulid' => (string) $createdPost->getUlid(),
            'status' => PostStatus::DRAFT->value,
            'title' => $title,
            'body' => $body,
            'createdAt' => $now->toIso8601String(),
            'updatedAt' => $now->toIso8601String(),
            'publishedAt' => null,
        ]);
    }

    public function test_admin_can_create_post_for_any_user(): void
    {
        $userProxy = UserFactory::createOne();
        $adminUserProxy = UserFactory::new()->admin()->create();
        $token = $this->JWTTokenManager->create($adminUserProxy->object());
        $postRepository = $this->entityManager->getRepository(Post::class);

        $this->client->request(
            'POST',
            "/api/v1/users/{$userProxy->getUlid()}/posts",
            [
                'json' => [
                    'title' => $title = $this->faker->sentence(),
                    'body' => $body = $this->faker->realTextBetween(300, 1000)
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertNotNull($postRepository->findOneBy([
            'status' => PostStatus::DRAFT->value,
            'author' => $userProxy->getId(),
            'title' => $title,
            'body' => $body
        ]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager, $this->JWTTokenManager, $this->faker, $this->client);
    }
}

