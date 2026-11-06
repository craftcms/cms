<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use SessionHandlerInterface;

/**
 * Holds back session writes until {@see flush()} is called.
 *
 * @internal
 *
 * @since 6.0.0
 */
class DeferredSessionHandler implements SessionHandlerInterface
{
    /** @var array{string, string}|null */
    private ?array $write = null;

    public function __construct(
        private readonly SessionHandlerInterface $handler,
    ) {}

    public function flush(): void
    {
        if ($this->write !== null) {
            $this->handler->write(...$this->write);
        }
    }

    public function open(string $path, string $name): bool
    {
        return $this->handler->open($path, $name);
    }

    public function close(): bool
    {
        return $this->handler->close();
    }

    public function read(string $id): string|false
    {
        return $this->handler->read($id);
    }

    public function write(string $id, string $data): bool
    {
        $this->write = [$id, $data];

        return true;
    }

    public function destroy(string $id): bool
    {
        return $this->handler->destroy($id);
    }

    public function gc(int $max_lifetime): int|false
    {
        return $this->handler->gc($max_lifetime);
    }
}
