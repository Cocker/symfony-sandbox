<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\Shared\VerificationCodeGenerator\StaticVerificationCodeGenerator;
use App\Api\User\Service\V1\VerificationService;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

class RequestPasswordResetControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private Client $client;
    private CacheItemPoolInterface $verificationPool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->verificationPool = static::getContainer()->get('verification_pool');
        $this->verificationPool->clear();
    }

    public function test_it_throws_error_if_invalid_email(): void
    {
        $this->client->request('POST', '/api/v1/password/request-reset', [
            'json' => ['email' => 'inv@lid@mail.com'],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'email: This value is not a valid email address.']);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_does_not_send_email_if_user_does_not_exist(): void
    {
        $this->client->request('POST', '/api/v1/password/request-reset', [
            'json' => ['email' => 'L4fKZ@example.com'],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_sends_email_if_user_exists(): void
    {
        $user = UserFactory::new()->create();

        $verificationService = $this->client->getContainer()->get(VerificationService::class);

        $this->client->request('POST', '/api/v1/password/request-reset', [
            'json' => ['email' => $user->getEmail()],
        ]);

        $this->assertEquals(
            StaticVerificationCodeGenerator::CODE,
            $verificationService->getCode(VerificationType::PASSWORD_RESET, $user->object())
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(1);

        /** @var Email $email */
        $email = $this->getMailerMessage();
        $this->assertEquals('Reset password', $email->getSubject());
        $this->assertStringContainsString(StaticVerificationCodeGenerator::CODE, $email->getTextBody());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->verificationPool->clear();

        unset($this->client, $this->verificationPool);
    }
}
