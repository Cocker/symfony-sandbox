<?php

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\V1\EmailService;
use App\Service\Redis\RedisService;
use App\Service\VerificationCode\Enum\VerificationType;
use App\Service\VerificationCode\VerificationCodeGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;

class SendEmailVerificationEmailControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;
    private Client $client;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->client = static::createClient();
    }

    public function test_it_doesnt_send_email_if_user_doesnt_exist(): void
    {
        $this->client->request('POST', '/api/v1/email/send-verification', [
            'body' => json_encode(['email' => 'some@mail.com'], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_doesnt_send_email_if_user_is_already_verified(): void
    {
        $user = UserFactory::new()->create();

        $this->client->request('POST', '/api/v1/email/send-verification',  [
            'body' => json_encode(['email' => $user->getEmail()], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_sends_email_if_user_exists(): void
    {
        $user = UserFactory::new()->unverified()->create();

        $this->client->request('POST', '/api/v1/email/send-verification',  [
            'body' => json_encode(['email' => $user->getEmail()], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(1);
    }

    public function test_it_overwrites_existing_verification_code(): void
    {
        $user = UserFactory::new()->unverified()->create();

        $verificatonCodeGeneratorMock = $this->createMock(VerificationCodeGeneratorInterface::class);
        $verificatonCodeGeneratorMock
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturn($code = '222222', $newCode = '333333');

        static::getContainer()->set(VerificationCodeGeneratorInterface::class, $verificatonCodeGeneratorMock);

        $emailService = static::getContainer()->get(EmailService::class);
        $emailService->sendVerificationCode($user->object());

        $redisService = static::getContainer()->get(RedisService::class);

        $this->client->request('POST', '/api/v1/email/send-verification',  [
            'body' => json_encode([
                'email' => $user->getEmail(),
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertEquals(
            $newCode,
            $redisService->get(VerificationType::VERIFY_EMAIL->fullKey($user->getUserIdentifier()))
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(2);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
