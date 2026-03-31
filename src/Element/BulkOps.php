<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\AfterBulkOp;
use CraftCms\Cms\Element\Events\BeforeBulkOp;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\ConnectionInterface;

#[Scoped]
class BulkOps
{
    /**
     * @var array<string, true>
     */
    private array $activeKeys = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * @return string[]
     */
    public function activeKeys(): array
    {
        return array_keys($this->activeKeys);
    }

    public function start(): string
    {
        $key = Str::random(10);

        event(new BeforeBulkOp($key));

        $this->resume($key);

        return $key;
    }

    public function resume(string $key): void
    {
        $this->activeKeys[$key] = true;
    }

    public function end(string $key): void
    {
        unset($this->activeKeys[$key]);

        event(new AfterBulkOp($key));

        if ($this->shouldBypassPersistence()) {
            return;
        }

        $this->connection
            ->table(Table::ELEMENTS_BULKOPS)
            ->where('key', $key)
            ->delete();
    }

    public function trackElement(ElementInterface $element): void
    {
        if (empty($this->activeKeys)) {
            return;
        }

        if ($this->shouldBypassPersistence()) {
            return;
        }

        $timestamp = now();

        foreach ($this->activeKeys() as $key) {
            $this->connection
                ->table(Table::ELEMENTS_BULKOPS)
                ->upsert([
                    'elementId' => $element->id,
                    'key' => $key,
                    'timestamp' => $timestamp,
                ], ['elementId', 'key']);
        }
    }

    public function ensure(callable $callback): mixed
    {
        if (! empty($this->activeKeys)) {
            return $callback();
        }

        $key = $this->start();

        try {
            return $callback();
        } finally {
            $this->end($key);
        }
    }

    private function shouldBypassPersistence(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        return request()->actionSegments() === ['app', 'update'];
    }
}
