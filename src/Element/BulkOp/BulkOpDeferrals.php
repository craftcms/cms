<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\BulkOp;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\Events\DeferredBulkOpReplay;
use CraftCms\Cms\Support\Facades\BulkOps;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

#[Singleton]
class BulkOpDeferrals
{
    /**
     * @var array<string, array<string, list<array{0: callable, 1: mixed}>>>
     */
    private array $handlers = [];

    /**
     * @var array<string, true>
     */
    private array $listening = [];

    /**
     * @var array<string, array<string, array<string, true>>>
     */
    private array $pending = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function defer(string $event, callable $handler, mixed $data = null, ?string $watchKey = null): void
    {
        $watchKey ??= $event;

        $this->handlers[$event][$watchKey][] = [$handler, $data];

        if (isset($this->listening[$event])) {
            return;
        }

        Event::listen($event, function () use ($event, $watchKey) {
            /**
             * @note We specifically use the Facade as otherwise the
             * scoped service would get locked in this singleton
             */
            foreach (BulkOps::activeKeys() as $key) {
                $this->pending[$key][$event][$watchKey] = true;
            }
        });

        $this->listening[$event] = true;
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

        $this->pending = [];
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
                foreach ($this->handlers[$event][$watchKey] ?? [] as [$handler, $data]) {
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
}
