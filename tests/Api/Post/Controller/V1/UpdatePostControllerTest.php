<?php

declare(strict_types=1);

namespace App\Tests\Api\Post\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Factory\PostFactory;
use App\Api\User\Entity\Factory\UserFactory;
use Carbon\CarbonImmutable;
use Faker\Factory;
use Faker\Generator;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class UpdatePostControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->faker = Factory::create();
    }

    public function test_it_returns_404_when_post_does_not_exist(): void
    {
        $randomUlid = new Ulid();
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());
        $this->assertTrue(true);

        $this->client->request(
            'PUT',
            "/api/v1/posts/$randomUlid",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
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
    public function test_can_not_update_if_not_draft(PostStatus $postStatus): void
    {
        $userProxy = UserFactory::createOne();
        $token = $this->JWTTokenManager->create($userProxy->object());
        $postProxy = PostFactory::new()
            ->withStatus($postStatus)
            ->withAuthor($userProxy->object())
            ->create()
        ;

        $this->client->request(
            'PUT',
            "/api/v1/posts/{$postProxy->getUlid()}",
            ['headers' => ['Authorization' => "Bearer $token"]],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $postProxy->refresh();

        $this->assertSame($postStatus, $postProxy->getStatus());
    }

    public function test_it_throws_validation_errors(): void
    {
        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withAuthor($userProxy->object())
            ->create()
        ;

        $token = $this->JWTTokenManager->create($userProxy->object());

        $response = $this->client->request(
            'PUT',
            "/api/v1/posts/{$postProxy->getUlid()}",
            [
                'headers' => ['Authorization' => "Bearer $token"],
                'json' => [
                    'title' => str_repeat('a', 256), // too long
                    'body' => 'body' // too short
                ]
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $responseArray = $response->toArray(throw: false);
        $validationErrors = $responseArray['hydra:description'];

        $this->assertStringContainsString('title', $validationErrors);
        $this->assertStringContainsString('body', $validationErrors);
    }

    public function test_user_can_not_update_others_posts(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $anotherUserPostProxy = PostFactory::new()
            ->withAuthor($anotherUserProxy->object())
            ->create()
        ;

        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'PUT',
            "/api/v1/posts/{$anotherUserPostProxy->getUlid()}",
            [
                'headers' => ['Authorization' => "Bearer $token"],
                'json' => [
                    'title' => $this->faker->sentence(),
                    'body' => $this->faker->realTextBetween(300, 1000),
                ]
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_update_own_post(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $userProxy = UserFactory::createOne();
        $postProxy = PostFactory::new()
            ->withAuthor($userProxy->object())
            ->create()
        ;

        $oldSlug = $postProxy->getSlug();

        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'PUT',
            "/api/v1/posts/{$postProxy->getUlid()}",
            [
                'headers' => ['Authorization' => "Bearer $token"],
                'json' => [
                    'title' => $newTitle = $this->faker->sentence(),
                    'body' => $newBody = $this->faker->realTextBetween(300, 1000),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertJsonEquals([
            'ulid' => (string) $postProxy->getUlid(),
            'status' => PostStatus::DRAFT->value,
            'title' => $newTitle,
            'body' => $newBody,
            'createdAt' => $postProxy->getCreatedAt()->toIso8601String(),
            'updatedAt' => $now->toIso8601String(),
            'publishedAt' => null,
        ]);

        $postProxy->refresh();

        $this->assertNotSame($oldSlug, $postProxy->getSlug());
        $this->assertSame($newTitle, $postProxy->getTitle());
        $this->assertSame($newBody, $postProxy->getBody());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->JWTTokenManager, $this->faker);
    }
}

