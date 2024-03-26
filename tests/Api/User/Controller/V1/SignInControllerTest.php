<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Controller\V1;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\User\Fixture\ExistingEmailUserFixture;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;

class SignInControllerTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    private EntityManagerInterface $entityManager;
    private DatabaseToolCollection $databaseToolCollection;
    private Client $client;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->databaseToolCollection = static::getContainer()
            ->get(DatabaseToolCollection::class);

        $this->client = static::createClient();
    }

    public function test_user_can_login(): void
    {
        $this->databaseToolCollection->get()->loadFixtures([ExistingEmailUserFixture::class]);

        $response = $this->client->request('POST','/api/v1/sign-in', [
            'body' => json_encode([
                'email' => ExistingEmailUserFixture::EXISTING_EMAIL,
                'password' => ExistingEmailUserFixture::DEFAULT_PASSWORD,
            ], JSON_THROW_ON_ERROR)
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $this->assertArrayHasKey('token', $json);


        // todo test the JWT token
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager, $this->databaseToolCollection);
    }
}
