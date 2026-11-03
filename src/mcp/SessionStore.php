<?php

namespace craft\mcp;

use Mcp\Server\Session\SessionStoreInterface;
use Symfony\Component\Uid\Uuid;
use yii\caching\CacheInterface;

/** Persists HTTP MCP sessions in Craft's configured cache. @since 5.12.0 */
class SessionStore implements SessionStoreInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly array $scope,
    ) {
    }

    public function exists(Uuid $id): bool
    {
        return $this->cache->exists([self::class, $this->scope, (string)$id]);
    }

    public function read(Uuid $id): string|false
    {
        return $this->cache->get([self::class, $this->scope, (string)$id]);
    }

    public function write(Uuid $id, string $data): bool
    {
        return $this->cache->set([self::class, $this->scope, (string)$id], $data, 3600);
    }

    public function destroy(Uuid $id): bool
    {
        return $this->cache->delete([self::class, $this->scope, (string)$id]);
    }

    public function gc(): array
    {
        // The cache backend expires sessions after an hour without a write.
        return [];
    }
}
