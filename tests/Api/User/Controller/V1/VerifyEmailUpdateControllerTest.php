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
        $this->client->request('POST', '/api/v1/email/verify-update', [
            'body' => json_encode([
                'code' => '123456',
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_throws_error_if_validation_fails(): void
    {
        $user = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('POST', '/api/v1/email/verify-update', [
            'body' => json_encode([
                'code' => 'invalid',
            ], JSON_THROW_ON_ERROR),
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_throws_error_if_invalid_code(): void
    {
        $user = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('POST', '/api/v1/email/verify-update', [
            'body' => json_encode([
                'code' => '123456',
            ], JSON_THROW_ON_ERROR),
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_verifies_new_email(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $user = UserFactory::new()->create();
        $user->setNewEmail($newEmail = 'new@mail.com',);
        $token = $this->JWTTokenManager->create($userObject = $user->object());

        $verificationService = static::getContainer()->get(VerificationService::class);
        $code = $verificationService->new(VerificationType::EMAIL_UPDATE, $userObject);

        $this->client->request('POST', '/api/v1/email/verify-update', [
            'body' => json_encode([
                'newEmail' => $newEmail,
                'code' => $code,
            ], JSON_THROW_ON_ERROR),
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $user->refresh();

        $this->assertJsonContains(['email' => $user->getEmail()]);

        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertTrue($now->eq($user->getEmailVerifiedAt()));

        $this->assertFalse($this->verificationPool->getItem(VerificationType::EMAIL_UPDATE->fullKey($user->object()))->isHit());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->verificationPool->clear();
    }
}
