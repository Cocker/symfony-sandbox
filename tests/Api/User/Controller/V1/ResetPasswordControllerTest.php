<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\Shared\VerificationCodeGenerator\VerificationCodeGeneratorInterface;
use App\Api\User\Service\V1\VerificationService;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private CacheItemPoolInterface $verificationPool;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->verificationPool = static::getContainer()->get('verification_pool');
        $this->verificationPool->clear();
    }

    public function test_it_throws_error_if_invalid_email(): void
    {
        $this->client->request('POST','/api/v1/password/reset', [
            'json' => [
                'email' => 'inv@lid@mail.com',
                'code' => '123456',
                'password' => '#$Qwerty123%^!'
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'email: This value is not a valid email address.']);
    }

    public function test_it_throws_error_if_invalid_code_format(): void
    {
        $this->client->request('POST','/api/v1/password/reset', [
            'json' => [
                'email' => 'test@mail.com',
                'code' => '123',
                'password' => '#$Qwerty123%^!',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'code: The verification code should be a numeric value of length 6. You provided: "123"']);
    }

    public function test_it_throws_error_if_user_does_not_exist(): void
    {
        $this->client->request('POST','/api/v1/password/reset', [
            'json' => [
                'email' => 'test@mail.com',
                'code' => '123456',
                'password' => '#$Qwerty123%^!',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJsonContains(['hydra:description' => 'Invalid verification code']);
    }

    public function test_it_throws_error_if_invalid_code(): void
    {
        $userProxy = UserFactory::new()->create();

        $verificationCodeGeneratorMock = $this->createMock(VerificationCodeGeneratorInterface::class);
        $verificationCodeGeneratorMock->expects($this->once())
            ->method('generate')
            ->willReturn($validCode = '123456')
        ;

        static::getContainer()->set(VerificationCodeGeneratorInterface::class, $verificationCodeGeneratorMock);

        $verificationService = static::getContainer()->get(VerificationService::class);
        $verificationService->new(VerificationType::PASSWORD_RESET, $userProxy->object());

        $this->client->request('POST','/api/v1/password/reset', [
            'json' => [
                'email' => $userProxy->getEmail(),
                'code' => '111111',
                'password' => '#$Qwerty123%^!',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJsonContains(['hydra:description' => 'Invalid verification code']);
    }

    public function test_it_throws_error_if_weak_password(): void
    {
        $userProxy = UserFactory::new()->create();

        $verificationCodeGeneratorMock = $this->createMock(VerificationCodeGeneratorInterface::class);
        $verificationCodeGeneratorMock->expects($this->once())
            ->method('generate')
            ->willReturn($validCode = '123456')
        ;

        static::getContainer()->set(VerificationCodeGeneratorInterface::class, $verificationCodeGeneratorMock);

        $verificationService = static::getContainer()->get(VerificationService::class);
        $verificationService->new(VerificationType::PASSWORD_RESET, $userProxy->object());

        $this->client->request('POST','/api/v1/password/reset', [
            'json' => [
                'email' => $userProxy->getEmail(),
                'code' => $validCode,
                'password' => 'qwerty123',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'plainPassword: The password strength is too low. Please use a stronger password.']);
    }

    public function test_it_resets_password(): void
    {
        $userProxy = UserFactory::new()->create();

        $verificationCodeGeneratorMock = $this->createMock(VerificationCodeGeneratorInterface::class);
        $verificationCodeGeneratorMock->expects($this->once())
            ->method('generate')
            ->willReturn($validCode = '123456')
        ;
        static::getContainer()->set(VerificationCodeGeneratorInterface::class, $verificationCodeGeneratorMock);

        $verificationService = static::getContainer()->get(VerificationService::class);
        $verificationService->new(VerificationType::PASSWORD_RESET, $userProxy->object());

        $userPasswordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->client->request('POST','/api/v1/password/reset', [
            'json' => [
                'email' => $userProxy->getEmail(),
                'code' => $validCode,
                'password' => $newPassword = '#$Qwerty123%^!',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $userProxy->refresh();

        $this->assertTrue($userPasswordHasher->isPasswordValid($userProxy->object(), $newPassword));
        $this->assertNull($verificationService->getCode(VerificationType::PASSWORD_RESET, $userProxy->object()));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->verificationPool->clear();

        unset($this->verificationPool, $this->client);
    }
}

