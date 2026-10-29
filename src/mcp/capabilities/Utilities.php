<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\base\UtilityInterface;
use craft\helpers\App;
use craft\mcp\attributes\RequiresPermission;
use craft\queue\Queue;
use craft\queue\QueueInterface;
use craft\utilities\ClearCaches;
use craft\utilities\QueueManager;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * The everyday tasks of the Caches and Queue Manager utilities.
 *
 * @since 5.12.0
 */
class Utilities
{
    /** @return array{caches: list<array{key: string, label: string, info: string|null}>} */
    #[McpTool(
        name: 'caches.list',
        description: 'Lists the caches that caches.clear can clear, with what each one holds.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('utility:clear-caches')]
    public function listCaches(): array
    {
        $this->authorize(ClearCaches::class);

        return ['caches' => array_map(static fn(array $option): array => [
            'key' => $option['key'],
            'label' => $option['label'],
            'info' => isset($option['info']) ? (string)$option['info'] : null,
        ], ClearCaches::cacheOptions())];
    }

    /**
     * @param  list<string>|null  $caches  Keys from caches.list. Omit to clear every cache.
     * @return array{cleared: list<string>}
     */
    #[McpTool(
        name: 'caches.clear',
        description: 'Clears Craft caches, as the Caches utility does. Pass keys from caches.list, or omit them to clear every cache.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    #[RequiresPermission('utility:clear-caches')]
    public function clearCaches(#[Schema(items: ['type' => 'string'])] ?array $caches = null): array
    {
        $this->authorize(ClearCaches::class);

        if ($caches !== null) {
            $unknown = array_diff($caches, array_column(ClearCaches::cacheOptions(), 'key'));

            if ($unknown !== []) {
                throw new ToolCallException('Unknown cache keys: ' . implode(', ', $unknown) . '. Call caches.list for the available keys.');
            }
        }

        return ['cleared' => ClearCaches::clear($caches ?? '*')];
    }

    /**
     * @param  int  $limit  The most jobs to list.
     * @return array{total: int, jobs: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'queue.status',
        description: 'Lists the jobs in the queue, with their status (1 = waiting, 2 = reserved, 3 = done, 4 = failed) and progress.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function queueStatus(#[Schema(minimum: 1, maximum: 100)] int $limit = 50): array
    {
        $this->authorize(QueueManager::class);
        $queue = $this->queue(QueueInterface::class);

        return [
            'total' => $queue->getTotalJobs(),
            'jobs' => $queue->getJobInfo($limit),
        ];
    }

    /**
     * @param  int  $maxSeconds  How long to keep starting jobs before returning. Jobs that haven’t run stay queued.
     * @return array{ran: int, remaining: int}
     */
    #[McpTool(
        name: 'queue.run',
        description: 'Runs waiting queue jobs until none are left or maxSeconds have passed, then reports how many jobs ran and how many are still waiting.',
        annotations: new ToolAnnotations(destructiveHint: false),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function runQueue(#[Schema(minimum: 1, maximum: 120)] int $maxSeconds = 30): array
    {
        $this->authorize(QueueManager::class);
        $queue = $this->queue(Queue::class);

        App::maxPowerCaptain();
        $deadline = time() + $maxSeconds;
        $ran = 0;

        while (time() < $deadline && $queue->executeJob()) {
            $ran++;
        }

        return [
            'ran' => $ran,
            'remaining' => $queue->getTotalWaiting(),
        ];
    }

    /** @return array{retried: true} */
    #[McpTool(
        name: 'queue.retry-all',
        description: 'Moves every failed queue job back into the queue to run again.',
        annotations: new ToolAnnotations(destructiveHint: false),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function retryAllJobs(): array
    {
        $this->authorize(QueueManager::class);
        $this->queue(QueueInterface::class)->retryAll();

        return ['retried' => true];
    }

    /** @return array{released: true} */
    #[McpTool(
        name: 'queue.release-all',
        description: 'Releases every job in the queue, removing waiting and failed jobs without running them.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function releaseAllJobs(): array
    {
        $this->authorize(QueueManager::class);
        $this->queue(QueueInterface::class)->releaseAll();

        return ['released' => true];
    }

    /** @param class-string<UtilityInterface> $utility */
    private function authorize(string $utility): void
    {
        if (!Craft::$app->getUtilities()->checkAuthorization($utility)) {
            throw new ToolCallException(sprintf('The %s utility is disabled or unavailable to this user.', $utility::id()));
        }
    }

    /**
     * @template T of QueueInterface
     * @param class-string<T> $class
     * @return T
     */
    private function queue(string $class): QueueInterface
    {
        $queue = Craft::$app->getQueue();

        if (!$queue instanceof $class) {
            throw new ToolCallException(sprintf('The queue class %s doesn’t support this tool.', get_class($queue)));
        }

        return $queue;
    }
}
