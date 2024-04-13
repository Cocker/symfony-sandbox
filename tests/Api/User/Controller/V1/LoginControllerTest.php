<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Event\UserLoginEvent;
use App\Api\User\EventSubscriber\UserLoginEventSubscriber;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends ApiTestCase
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

    public function test_it_throws_error_if_email_not_verified(): void
    {
        $userProxy = UserFactory::new()
            ->unverified()
            ->withPassword($plainPassword = '!@#Qwerty123$%^')
            ->create();

        $this->client->request('POST','/api/v1/auth/login', [
            'json' => [
                'email' => $userProxy->getEmail(),
                'password' => $plainPassword,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertJsonContains(['hydra:description' => 'Email not verified.']);
    }

    public function test_user_can_login(): void
    {
        CarbonImmutable::setTestNow($now = CarbonImmutable::now()->milliseconds(0));

        $userProxy = UserFactory::new()
            ->withPassword($plainPassword = '!@#Qwerty123$%^')
            ->create();

        $response = $this->client->request('POST','/api/v1/auth/login', [
            'json' => [
                'email' => $userProxy->getEmail(),
                'password' => $plainPassword,
            ],
        ]);

        /** @var TraceableEventDispatcher $traceableEventDispatcher */
        $traceableEventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);

        $onUserLoginListener = array_filter($traceableEventDispatcher->getCalledListeners(), function (array $listener) {
            return $listener['event'] === UserLoginEvent::class
                && $listener['pretty'] === (UserLoginEventSubscriber::class . '::onUserLogin')
            ;
        });

        $this->assertNotEmpty($onUserLoginListener);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertArrayHasKey('token', $json);

        $this->client->request('GET',"/api/v1/users/{$userProxy->getUlid()}");
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->client->request(
            'GET',
            "/api/v1/users/{$userProxy->getUlid()}",
            ['headers' => ['Authorization' => sprintf("Bearer {$json['token']}")]]
        );
        $this->assertResponseIsSuccessful();
    }

    public function test_it_returns_401_if_invalid_credentials_are_provided(): void
    {
        $user = UserFactory::new()
            ->withPassword('!@#Qwerty123$%^')
            ->create();

        $this->client->request('POST','/api/v1/auth/login', [
            'json' => [
                'email' => 'invalid',
                'password' => 'invalid',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager, $this->client);
    }
}
