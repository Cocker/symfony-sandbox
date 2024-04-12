<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\Shared\VerificationCodeGenerator\StaticVerificationCodeGenerator;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class RequestEmailUpdateControllerTest extends ApiTestCase
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

        $this->client->request('POST', "/api/v1/users/$randomUlid/email/request-update");

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_throws_error_if_validation_fails(): void
    {
        $user = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('POST', "/api/v1/users/{$user->getUlid()}/email/request-update", [
            'json' => ['email' => 'inva@lid@mail.com'],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_it_throws_error_if_email_already_exists(): void
    {
        $user = UserFactory::new()->create();
        UserFactory::new()->withEmail($existingEmail = 'existing@mail.com')->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('POST', "/api/v1/users/{$user->getUlid()}/email/request-update", [
            'json' => ['email' => $existingEmail],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'email: User with this email already exists.']);
        $this->assertQueuedEmailCount(0);
    }

    public function test_it_throws_error_if_same_email(): void
    {
        $user = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('POST', "/api/v1/users/{$user->getUlid()}/email/request-update", [
            'json' => ['email' => $user->getEmail()],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertQueuedEmailCount(0);
    }

    public function test_user_can_not_request_email_update_for_other_user(): void
    {
        $userProxy = UserFactory::createOne();
        $anotherUserProxy = UserFactory::createOne();
        $newEmail = 'new@mail.com';

        $token = $this->JWTTokenManager->create($userProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/users/{$anotherUserProxy->getUlid()}/email/request-update",
            [
                'json' => ['email' => $newEmail],
                'headers' => ['Authorization' => "Bearer $token"],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_sends_email(): void
    {
        $userProxy = UserFactory::new()->create();
        $token = $this->JWTTokenManager->create($userObject = $userProxy->object());
        $userObject->setNewEmail($newEmail = 'new@mail.com',);

        $this->client->request('POST', "/api/v1/users/{$userProxy->getUlid()}/email/request-update", [
            'json' => ['email' => $newEmail],
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(2);

        $this->assertEquals(
            StaticVerificationCodeGenerator::CODE,
            $this->verificationPool->getItem(VerificationType::EMAIL_UPDATE->fullKey($userObject))->get()
        );

        [$verificationEmail, $notificationEmail] = $this->getMailerMessages();
        $this->assertEquals($newEmail, $verificationEmail->getTo()[0]->getAddress());
        $this->assertEquals('Verify your new email', $verificationEmail->getSubject());
        $this->assertStringContainsString(StaticVerificationCodeGenerator::CODE, $verificationEmail->getTextBody());

        $this->assertEquals($userObject->getEmail(), $notificationEmail->getTo()[0]->getAddress());
        $this->assertEquals('Security notification', $notificationEmail->getSubject());
    }

    public function test_admin_can_request_email_update_for_any_user(): void
    {
        $userProxy = UserFactory::createOne();
        $adminUserProxy = UserFactory::new()->admin()->create();
        $newEmail = 'new@mail.com';

        $token = $this->JWTTokenManager->create($adminUserProxy->object());

        $this->client->request(
            'POST',
            "/api/v1/users/{$userProxy->getUlid()}/email/request-update",
            [
                'json' => ['email' => $newEmail],
                'headers' => ['Authorization' => "Bearer $token"]
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertQueuedEmailCount(2);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->verificationPool->clear();
    }
}
