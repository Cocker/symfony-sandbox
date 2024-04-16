<?php

declare(strict_types=1);

namespace App\Tests\Api\User\EventSubscriber;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\Entity\Factory\UserFactory;
use App\Api\User\Entity\Factory\UserLoginFactory;
use App\Api\User\Entity\UserLogin;
use App\Api\User\Event\UserLoginEvent;
use App\Api\User\EventSubscriber\UserLoginEventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

class UserLoginEventSubscriberTest extends KernelTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();
    }
    public function test_it_creates_record_but_does_not_send_email_if_not_suspicious_login(): void
    {
        $userProxy = UserFactory::new()->create();
        $userLoginProxy = UserLoginFactory::new()
            ->withIp($clientIp = '127.127.127.127')
            ->withUserAgent($userAgent = 'Test user Agent')
            ->withCauser($userProxy->object())
            ->create()
        ;

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'email' => $userProxy->getEmail(),
                'password' => 'anypassword',
            ])
        ;
        $requestMock->expects($this->once())
            ->method('getClientIp')
            ->willReturn($clientIp)
        ;

        $headerBagMock = $this->createMock(HeaderBag::class);
        $headerBagMock->expects($this->once())
            ->method('get')
            ->with('User-Agent')
            ->willReturn($userAgent)
        ;
        $requestMock->headers = $headerBagMock;

        $userLoginSubscriber = static::getContainer()->get(UserLoginEventSubscriber::class);

        $userLoginSubscriber->onUserLogin(
            new UserLoginEvent($userProxy->getId(), SignInDTO::fromRequest($requestMock))
        );

        $userLoginRepository = $this->entityManager->getRepository(UserLogin::class);
        $userLogin = $userLoginRepository->findOneBy([
            'causer' => $userProxy->getId(),
            'ip' => $clientIp,
            'userAgent' => $userAgent,
        ]);

        $this->assertNotNull($userLogin);
        $this->assertTrue(Ulid::isValid((string) $userLogin->getUlid()));

        $this->assertQueuedEmailCount(0);
    }

    public function test_it_creates_record_and_sends_email_if_unknown_ip(): void
    {
        $userProxy = UserFactory::new()->create();
        $userLoginProxy = UserLoginFactory::new()
            ->withIp($clientIp = '127.127.127.127')
            ->withUserAgent($userAgent = 'Test user Agent')
            ->withCauser($userProxy->object())
            ->create()
        ;

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'email' => $userProxy->getEmail(),
                'password' => 'anypassword',
            ])
        ;
        $requestMock->expects($this->once())
            ->method('getClientIp')
            ->willReturn($newClientIp = '244.178.44.111')
        ;

        $headerBagMock = $this->createMock(HeaderBag::class);
        $headerBagMock->expects($this->once())
            ->method('get')
            ->with('User-Agent')
            ->willReturn($userAgent)
        ;
        $requestMock->headers = $headerBagMock;

        $userLoginSubscriber = static::getContainer()->get(UserLoginEventSubscriber::class);

        $userLoginSubscriber->onUserLogin(
            new UserLoginEvent($userProxy->getId(), SignInDTO::fromRequest($requestMock))
        );

        $userLoginRepository = $this->entityManager->getRepository(UserLogin::class);
        $userLogin = $userLoginRepository->findOneBy([
            'causer' => $userProxy->getId(),
            'ip' => $newClientIp,
            'userAgent' => $userAgent,
        ]);

        $this->assertNotNull($userLogin);

        $this->assertQueuedEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertSame('Suspicious Login', $email->getSubject());
        $this->assertStringContainsString($newClientIp, $email->getTextBody());
        $this->assertStringContainsString($userAgent, $email->getTextBody());
    }

    public function test_it_creates_record_and_sends_email_if_unknown_user_agent(): void
    {
        $userProxy = UserFactory::new()->create();
        $userLoginProxy = UserLoginFactory::new()
            ->withIp($clientIp = '127.127.127.127')
            ->withUserAgent($userAgent = 'Test user Agent')
            ->withCauser($userProxy->object())
            ->create()
        ;

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'email' => $userProxy->getEmail(),
                'password' => 'anypassword',
            ])
        ;
        $requestMock->expects($this->once())
            ->method('getClientIp')
            ->willReturn($clientIp)
        ;

        $headerBagMock = $this->createMock(HeaderBag::class);
        $headerBagMock->expects($this->once())
            ->method('get')
            ->with('User-Agent')
            ->willReturn($newUserAgent = 'New user agent')
        ;
        $requestMock->headers = $headerBagMock;

        $userLoginSubscriber = static::getContainer()->get(UserLoginEventSubscriber::class);

        $userLoginSubscriber->onUserLogin(
            new UserLoginEvent($userProxy->getId(), SignInDTO::fromRequest($requestMock))
        );

        $userLoginRepository = $this->entityManager->getRepository(UserLogin::class);
        $userLogin = $userLoginRepository->findOneBy([
            'causer' => $userProxy->getId(),
            'ip' => $clientIp,
            'userAgent' => $newUserAgent,
        ]);

        $this->assertNotNull($userLogin);

        $this->assertQueuedEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertSame('Suspicious Login', $email->getSubject());
        $this->assertStringContainsString($newUserAgent, $email->getTextBody());
        $this->assertStringContainsString($clientIp, $email->getTextBody());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
