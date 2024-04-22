<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Factory\PostFactory;
use App\Api\Post\Entity\PostComment;
use App\Api\User\Entity\Factory\UserFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class CreatePostCommentControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;
    private Generator $faker;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->faker = Factory::create();
    }

    public function test_it_returns_404_if_post_does_not_exist(): void
    {
        $randomUlid = new Ulid();
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/posts/$randomUlid/comments",
            ['headers' => ['Authorization' => "Bearer $token"]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function providesPostCommentStatuses(): \Generator
    {
        yield [PostStatus::PENDING];
        yield [PostStatus::REJECTED];
    }

    /**
     * @dataProvider providesPostCommentStatuses
     */
    public function test_user_can_not_create_comment_if_post_not_published(PostStatus $status): void
    {
        $postProxy = PostFactory::new()
            ->withStatus($status)
            ->create()
        ;
        $user = $postProxy->getAuthor();
        $token = $this->JWTTokenManager->create($user);

        $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/comments",
            [
                'json' => ['content' => $this->faker->realTextBetween(1, 300)],
                'headers' => ['Authorization' => "Bearer $token"]
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_returns_validation_errors(): void
    {
        $postProxy = PostFactory::new()
            ->withStatus(PostStatus::PUBLISHED)
            ->create()
        ;
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $response = $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/comments",
            [
                'json' => [
                    'comment' => $this->faker->realTextBetween(400, 1000),
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $validationErrors = $response->toArray(throw: false)['hydra:description'];
        $this->assertStringContainsString('content', $validationErrors);
    }

    public function test_user_can_create_comment(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $postProxy = PostFactory::new()
            ->withStatus(PostStatus::PUBLISHED)
            ->create()
        ;
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $postCommentRepository = $this->entityManager->getRepository(PostComment::class);

        $this->client->request(
            'POST',
            "/api/v1/posts/{$postProxy->getUlid()}/comments",
            [
                'json' => [
                    'content' => $content = $this->faker->realTextBetween(1, 300),
                ],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->assertJsonContains([
            'content' => $content,
            'createdAt' => $now->toIso8601String(),
            'updatedAt' => $now->toIso8601String(),
        ]);

        $this->assertNotNull($postCommentRepository->findOneBy([
            'content' => $content,
            'author' => $userProxy->object(),
            'post' => $postProxy->object(),
        ]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();

        unset($this->entityManager, $this->JWTTokenManager, $this->faker, $this->client);
    }
}
