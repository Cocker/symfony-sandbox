<?php

declare(strict_types=1);

namespace App\Service\Redis;

use Redis;

class RedisService
{
    public function __construct(
        string $redisHost,
        int $redisPort,
        #[\SensitiveParameter] string $redisPassword,
        int $redisDatabase,
    ) {
        $this->redis = new Redis(); // todo DI
        $this->redis->connect($redisHost, $redisPort);
        $this->redis->auth($redisPassword);
        $this->redis->select($redisDatabase);
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $this->redis->set($key, $value);

        if ($ttlSeconds !== null) {
            $this->redis->expire($key, $ttlSeconds);
        }
    }

    public function get(string $key): mixed
    {
        return $this->redis->get($key);
    }

    public function getTtlSeconds(string $key): ?int
    {
        $ttl = $this->redis->ttl($key);

        if ($ttl === false || $ttl < 0) {
            return null;
        }

        return $ttl;
    }

    public function has(string $key): bool
    {
        return $this->redis->get($key) !== false;
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function flushDb(bool $confirmation = false): void
    {
        if (! $confirmation) {
            return;
        }

        $this->redis->flushDB();
    }
}
