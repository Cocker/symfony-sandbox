<?php

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\Shared\VerificationCodeGenerator\StaticVerificationCodeGenerator;
use App\Api\User\Service\Shared\VerificationCodeGenerator\VerificationCodeGeneratorInterface;
use App\Api\User\Service\V1\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;

class SendEmailVerificationEmailControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;
    private Client $client;
    private CacheItemPoolInterface $verificationPool;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->client = static::createClient();
        $this->verificationPool = static::getContainer()->get('verification_pool');
        $this->verificationPool->clear();
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

        $this->assertFalse($this->verificationPool->getItem(VerificationType::EMAIL_VERIFY->fullKey($user->object()))->isHit());
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

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->verificationPool->getItem(VerificationType::EMAIL_VERIFY->fullKey($user->object()));
        $this->assertEquals(StaticVerificationCodeGenerator::CODE, $cacheItem->get());
    }

    public function test_it_overwrites_existing_verification_code(): void
    {
        $user = UserFactory::new()->unverified()->create();

        $verificatonCodeGeneratorMock = $this->createMock(VerificationCodeGeneratorInterface::class);
        $verificatonCodeGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->willReturn($newCode = '333333');

        static::getContainer()->set(VerificationCodeGeneratorInterface::class, $verificatonCodeGeneratorMock);

        $cacheItem = $this->verificationPool->getItem(VerificationType::EMAIL_VERIFY->fullKey($user->object()));
        $cacheItem->set($oldCode = '222222');
        $this->verificationPool->save($cacheItem);

        $this->client->request('POST', '/api/v1/email/send-verification',  [
            'body' => json_encode([
                'email' => $user->getEmail(),
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertEquals(
            $newCode,
            $this->verificationPool->getItem(VerificationType::EMAIL_VERIFY->fullKey($user->object()))->get()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
