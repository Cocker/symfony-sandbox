<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\V1\VerificationService;
use Carbon\CarbonImmutable;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class VerifyEmailUpdateControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;
    private CacheItemPoolInterface $verificationPool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->verificationPool = static::getContainer()->get('verification_pool');
        $this->verificationPool->clear();
    }

    public function test_it_cannot_be_accessed_if_not_authenticated(): void
    {
        $randomUlid = new Ulid();

        $this->client->request('POST', "/api/v1/users/{$randomUlid}/email/verify-update", [
            'json' => ['code' => '123456'],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_throws_error_if_validation_fails(): void
    {
        $userProxy = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request('POST', "/api/v1/users/{$userProxy->getUlid()}/email/verify-update", [
            'json' => ['code' => 'invalid'],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_throws_error_if_invalid_code(): void
    {
        $userProxy = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request('POST', "/api/v1/users/{$userProxy->getUlid()}/email/verify-update", [
            'json' => ['code' => '123456'],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertQueuedEmailCount(0);
    }

    public function test_user_can_not_update_the_email_of_other_user(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();

        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request('POST', "/api/v1/users/{$anotherUserProxy->getUlid()}/email/verify-update", [
            'json' => [
                'newEmail' => 'new@mail.com',
                'code' => '123456',
            ],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_verifies_new_email(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $userProxy = UserFactory::new()->create();
        $userProxy->setNewEmail($newEmail = 'new@mail.com',);
        $token = $this->JWTTokenManager->create($userObject = $userProxy->object());

        $verificationService = static::getContainer()->get(VerificationService::class);
        $code = $verificationService->new(VerificationType::EMAIL_UPDATE, $userObject);

        $this->client->request('POST', "/api/v1/users/{$userProxy->getUlid()}/email/verify-update", [
            'json' => [
                'newEmail' => $newEmail,
                'code' => $code,
            ],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $userProxy->refresh();

        $this->assertJsonContains(['email' => $userProxy->getEmail()]);

        $this->assertEquals($newEmail, $userProxy->getEmail());
        $this->assertTrue($now->eq($userProxy->getEmailVerifiedAt()));

        $this->assertNull($verificationService->getCode(VerificationType::EMAIL_UPDATE, $userProxy->object()));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->verificationPool->clear();

        unset($this->verificationPool, $this->client, $this->JWTTokenManager);
    }
}
