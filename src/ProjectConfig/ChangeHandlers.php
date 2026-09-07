<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use Closure;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;

class ChangeHandlers
{
    /** @var list<array{event: string, pattern: string, depth: int, handler: callable, data: mixed}> */
    private array $handlers = [];

    /** @var list<array{event: ConfigEvent, handler: callable}> */
    private array $deferred = [];

    public function register(string $event, string $path, callable $handler, mixed $data): void
    {
        $pattern = str_replace('\{uid\}', '('.ProjectConfig::UID_PATTERN.')', preg_quote($path, '~'));
        $this->handlers[] = [
            'event' => $event,
            'pattern' => '~^('.$pattern.')(?:\.|$)~',
            'depth' => ProjectConfigHelper::pathDepth($path),
            'handler' => $handler,
            'data' => $data,
        ];

        // Make sure the event handlers are sorted from least-to-most specific.
        usort($this->handlers, fn (array $a, array $b): int => $a['depth'] <=> $b['depth']);
    }

    /** @param Closure(string, ConfigEvent): void $parentEvent */
    public function dispatch(ConfigEvent $event, Closure $parentEvent): void
    {
        foreach ($this->handlers as $registration) {
            if (! $event instanceof $registration['event'] || ! preg_match($registration['pattern'], $event->path, $matches)) {
                continue;
            }

            $path = $matches[1];

            if ($path !== $event->path) {
                $parentEvent($path, $event);

                continue;
            }

            $notification = $event;

            // Skip the full match and containing path, leaving only the {uid} captures.
            $notification->tokenMatches = array_slice($matches, 2);
            $notification->data = $registration['data'];

            try {
                ($registration['handler'])($notification);
            } finally {
                $notification->tokenMatches = null;
                $notification->data = null;
            }
        }
    }

    public function defer(ConfigEvent $event, callable $handler): void
    {
        $this->deferred[] = ['event' => clone $event, 'handler' => $handler];
    }

    public function runDeferred(int $maxDefers): void
    {
        $remaining = count($this->deferred) + $maxDefers;

        while ($this->deferred !== []) {
            if ($remaining-- <= 0) {
                $paths = array_unique(array_map(fn (array $item): string => $item['event']->path, $this->deferred));

                throw new OperationAbortedException('Unable to resolve deferred project config paths: '.implode(', ', $paths));
            }

            ['event' => $event, 'handler' => $handler] = array_shift($this->deferred);

            try {
                $handler($event);
            } finally {
                $event->tokenMatches = null;
                $event->data = null;
            }
        }
    }

    public function reset(): void
    {
        $this->deferred = [];
    }
}
