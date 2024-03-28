<?php

namespace App\Tests\Service\Redis;

use App\Service\Redis\RedisService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RedisServiceTest extends KernelTestCase
{
    protected RedisService $redisService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redisService = static::getContainer()->get(RedisService::class);
        $this->redisService->flushDb(confirmation: true);
    }

    public function test_it_can_set_the_value(): void
    {
        $this->redisService->set($key = 'key', $value = 'value');

        $this->assertEquals($value, $this->redisService->get($key));
    }

    public function test_it_can_set_with_ttl(): void
    {
        $this->redisService->set($key = 'key', 'value', $ttlSeconds = 2);

        sleep($ttlSeconds + 1);

        $this->assertFalse($this->redisService->get($key));
    }

    public function test_get_returns_false_if_key_does_not_exist(): void
    {
        $this->assertFalse($this->redisService->get('unknown_key'));
    }

    public function test_has_returns_false_if_key_does_not_exist(): void
    {
        $this->assertFalse($this->redisService->has('unknown_key'));
    }

    public function test_has_returns_true_if_key_exists(): void
    {
        $this->redisService->set($key = 'key', 'value');
        $this->assertTrue($this->redisService->has($key));
    }

    public function test_it_can_delete_a_key(): void
    {
        $this->redisService->set($key = 'key', 'value');
        $this->redisService->delete($key);
        $this->assertFalse($this->redisService->has($key));
    }

    public function test_it_can_return_the_ttl_of_a_key(): void
    {
        $this->redisService->set($key = 'key', 'value', $ttlSeconds = 60);
        $this->assertEquals($ttlSeconds, $this->redisService->getTtlSeconds($key));
    }

    public function test_get_ttl_returns_null_if_key_does_not_exist(): void
    {
        $this->assertNull($this->redisService->getTtlSeconds('some_key'));
    }

    public function test_it_doesnt_flush_db_without_confirmation(): void
    {
        $this->redisService->set($key = 'key', 'value');
        $this->redisService->flushDb();
        $this->assertTrue($this->redisService->has($key));
    }

    public function test_it_can_flush_db(): void
    {
        $this->redisService->set($key = 'key', 'value');
        $this->redisService->flushDb(confirmation:  true);
        $this->assertFalse($this->redisService->has($key));
    }

    protected function tearDown() : void
    {
        unset($this->redisService);
    }
}
