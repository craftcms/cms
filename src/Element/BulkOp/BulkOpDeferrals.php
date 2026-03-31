<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\Events\DeferredBulkOpReplay;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

#[Scoped]
class BulkOpDeferrals
{
    /**
     * @var array<string, array<string, list<array{0: callable, 1: mixed}>>>
     */
    private static array $handlers = [];

    /**
     * @var array<string, true>
     */
    private static array $listening = [];

    /**
     * @var array<string, array<string, array<string, true>>>
     */
    private array $pending = [];

    public function __construct(
        private readonly BulkOps $bulkOps,
        private readonly ConnectionInterface $connection,
    ) {}

    public function defer(string $event, callable $handler, mixed $data = null, ?string $watchKey = null): void
    {
        $watchKey ??= $event;

        self::$handlers[$event][$watchKey][] = [$handler, $data];

        if (isset(self::$listening[$event])) {
            return;
        }

        Event::listen($event, function () use ($event, $watchKey) {
            foreach ($this->bulkOps->activeKeys() as $key) {
                $this->pending[$key][$event][$watchKey] = true;
            }
        });

        self::$listening[$event] = true;
    }

    public function persistPending(): void
    {
        if (empty($this->pending)) {
            return;
        }

        $timestamp = now();

        foreach ($this->pending as $key => $events) {
            foreach ($events as $event => $watchKeys) {
                foreach (array_keys($watchKeys) as $watchKey) {
                    $this->connection->table(Table::BULKOPEVENTS)
                        ->upsert([
                            'key' => $key,
                            'senderClass' => $event,
                            'eventName' => $watchKey,
                            'timestamp' => $timestamp,
                        ], ['key', 'senderClass', 'eventName']);
                }
            }
        }
    }

    public function replay(string $key): void
    {
        $triggers = Arr::pull($this->pending, $key, []);

        $storedTriggers = $this->connection
            ->table(Table::BULKOPEVENTS)
            ->select(['senderClass', 'eventName'])
            ->where('key', $key)
            ->get();

        if ($storedTriggers->isNotEmpty()) {
            $this->connection
                ->table(Table::BULKOPEVENTS)
                ->where('key', $key)
                ->delete();

            foreach ($storedTriggers as $trigger) {
                $triggers[$trigger->senderClass][$trigger->eventName] = true;
            }
        }

        foreach ($triggers as $event => $watchKeys) {
            foreach (array_keys($watchKeys) as $watchKey) {
                foreach (self::$handlers[$event][$watchKey] ?? [] as [$handler, $data]) {
                    $handler(new DeferredBulkOpReplay(
                        key: $key,
                        event: $event,
                        watchKey: $watchKey,
                        data: $data,
                    ));
                }
            }
        }
    }

    public static function reset(): void
    {
        self::$handlers = [];
        self::$listening = [];
    }
}
