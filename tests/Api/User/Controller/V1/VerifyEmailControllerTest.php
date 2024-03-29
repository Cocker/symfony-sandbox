<?php

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Service\V1\EmailService;
use App\Service\VerificationCode\StaticVerificationCodeGenerator;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailControllerTest extends ApiTestCase
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

    public function it_throws_error_if_validation_fails(): void
    {
        $response = $this->client->request('POST', '/api/v1/email/verify',  [
            'body' => json_encode([
                'email' => 'inva@lid@mail.com',
                'code' => '',
            ], JSON_THROW_ON_ERROR)
        ]);

        $responseArray = $response->toArray(throw: false);
        $validationErorrs = $responseArray['hydra:description'];

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertStringContainsString('email', $validationErorrs);
        $this->assertStringContainsString('code', $validationErorrs);
    }

    public function test_it_throws_error_if_invalid_code(): void
    {
        $userProxy = UserFactory::new()
            ->unverified()
            ->create();

        $this->client->request('POST', '/api/v1/email/verify',  [
            'body' => json_encode([
                'email' => $userProxy->getEmail(),
                'code' => '222222',
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJsonContains(['hydra:description' => 'Invalid verification code']);
    }

    public function test_it_verifies_email(): void
    {
        $now = CarbonImmutable::now()->milliseconds(0);
        CarbonImmutable::setTestNow($now);

        $userProxy = UserFactory::new()
            ->unverified()
            ->create();

        $emailService = self::getContainer()->get(EmailService::class);
        $emailService->sendVerificationCode($userProxy->object());

        $this->client->request('POST', '/api/v1/email/verify',  [
            'body' => json_encode([
                'email' => $userProxy->getEmail(),
                'code' => StaticVerificationCodeGenerator::CODE,
            ], JSON_THROW_ON_ERROR)
        ]);

        $userProxy->refresh();

        $this->assertEquals(UserStatus::ACTIVE, $userProxy->getStatus());
        $this->assertTrue($now->eq($userProxy->getEmailVerifiedAt()));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
