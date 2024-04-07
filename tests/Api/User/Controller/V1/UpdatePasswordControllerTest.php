<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Entity\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdatePasswordControllerTest extends ApiTestCase
{
    private Client $client;
    private JWTTokenManagerInterface $JWTTokenManager;
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    public function test_it_throws_error_if_not_authenticated(): void
    {
        $this->client->request('PUT', '/api/v1/password');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_validation_error_if_invalid_current_password(): void
    {
        $user = UserFactory::new()
            ->withPassword('ValidPassword123!')
            ->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('PUT', '/api/v1/password', [
            'body' => json_encode([
                'password' => 'invalid-password',
                'newPassword' => '!#123Qwerty123$%',
            ], JSON_THROW_ON_ERROR),
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'password: This value should be the user\'s current password.']);
    }

    public function test_it_returns_validation_error_if_weak_new_password(): void
    {
        $user = UserFactory::new()
            ->withPassword($password = 'ValidPassword123!')
            ->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('PUT', '/api/v1/password', [
            'body' => json_encode([
                'password' => $password,
                'newPassword' => 'weakpassword',
            ], JSON_THROW_ON_ERROR),
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(['hydra:description' => 'plainPassword: The password strength is too low. Please use a stronger password.']);
    }

    public function test_it_updates_password(): void
    {
        $userPasswordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = UserFactory::new()
            ->withPassword($oldPassword = 'ValidPassword123!')
            ->create();
        $token = $this->JWTTokenManager->create($user->object());

        $this->client->request('PUT', '/api/v1/password', [
            'body' => json_encode([
                'password' => $oldPassword,
                'newPassword' => $newPassword = '!#123Qwerty123$%',
            ], JSON_THROW_ON_ERROR),
            'headers' => ['Authorization' => "Bearer $token"],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $user->refresh();

        $this->assertTrue($userPasswordHasher->isPasswordValid($user->object(), $newPassword));
    }
}

