<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class AccessControlTest extends ApiTestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function providesEndpoints(): \Generator
    {
        $randomUlid = new Ulid();

        // V1

        // User
        yield ['GET', "/api/v1/users/$randomUlid"];
        yield ['PUT', "/api/v1/users/$randomUlid"];
        yield ['POST', "/api/v1/users/$randomUlid/email/request-update"];
        yield ['POST', "/api/v1/users/$randomUlid/email/verify-update"];
        yield ['PUT', "/api/v1/users/$randomUlid/password"];

        // Post
        yield ['POST', "/api/v1/post-comments/$randomUlid/approve"];
        yield ['POST', "/api/v1/posts/$randomUlid/complete"];
        yield ['POST', "/api/v1/posts/$randomUlid/comments"];
        yield ['POST', "/api/v1/users/$randomUlid/posts"];
        yield ['GET', "/api/v1/posts/$randomUlid/comments"];
        yield ['GET', "/api/v1/users/$randomUlid/post-comments"];
        yield ['GET', "/api/v1/posts/$randomUlid"];
        yield ['GET', "/api/v1/posts"];
        yield ['GET', "/api/v1/users/$randomUlid/posts"];
        yield ['POST', "/api/v1/posts/$randomUlid/publish"];
        yield ['POST', "/api/v1/post-comments/$randomUlid/reject"];
        yield ['POST', "/api/v1/posts/$randomUlid/reject"];
        yield ['PUT', "/api/v1/posts/$randomUlid"];
    }

    /**
     * @dataProvider providesEndpoints
     */
    public function test_it_returns_error_if_unauthenticated(string $method, string $url): void
    {
        $this->client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client);
    }
}