<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Artisan;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

use function CraftCms\Cms\maxPowerCaptain;

/**
 * The everyday tasks of the Caches, Queue Manager and Asset Indexes utilities.
 *
 * @since 6.0.0
 */
readonly class Utilities
{
    public function __construct(
        private McpActor $actor,
        private GeneralConfig $generalConfig,
        private JobProgress $jobProgress,
        private Volumes $volumes,
    ) {}

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

        return ['caches' => array_map(static fn (array $option): array => [
            'key' => $option['key'],
            'label' => $option['label'],
            'info' => isset($option['info']) ? (string) $option['info'] : null,
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
    public function clearCaches(?array $caches = null): array
    {
        $this->authorize(ClearCaches::class);

        if ($caches !== null) {
            $unknown = array_diff($caches, array_column(ClearCaches::cacheOptions(), 'key'));

            if ($unknown !== []) {
                throw new ToolCallException('Unknown cache keys: '.implode(', ', $unknown).'. Call caches.list for the available keys.');
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
        description: 'Lists the jobs in the queue, with their status and progress.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function queueStatus(#[Schema(minimum: 1, maximum: 100)] int $limit = 50): array
    {
        $this->authorize(QueueManager::class);

        return [
            'total' => $this->jobProgress->getTotalJobs(),
            'jobs' => $this->jobProgress->getJobInfo($limit)->map->toArray()->values()->all(),
        ];
    }

    /**
     * @param  int  $maxSeconds  How long to keep running jobs before returning. Jobs that are still pending stay queued.
     * @return array{remaining: int}
     */
    #[McpTool(
        name: 'queue.run',
        description: 'Runs pending queue jobs until the queue is empty or maxSeconds have passed, then reports how many jobs remain.',
        annotations: new ToolAnnotations(destructiveHint: false),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function runQueue(#[Schema(minimum: 1, maximum: 120)] int $maxSeconds = 30): array
    {
        $this->authorize(QueueManager::class);

        if ($this->jobProgress->hasPendingJobs()) {
            maxPowerCaptain();

            Artisan::call('queue:work', [
                '--queue' => collect([Cms::config()->queueName, Cms::config()->lowPriorityQueueName])
                    ->unique()
                    ->join(','),
                '--memory' => Cms::config()->phpMaxMemoryLimit ?: '1536M',
                '--stop-when-empty' => true,
                '--max-time' => $maxSeconds,
            ]);
        }

        return ['remaining' => $this->jobProgress->getTotalJobs()];
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

        Artisan::call('queue:retry', ['id' => 'all']);

        return ['retried' => true];
    }

    /** @return array{released: true} */
    #[McpTool(
        name: 'queue.release-all',
        description: 'Releases every job in the queue, removing pending and failed jobs without running them.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('utility:queue-manager')]
    public function releaseAllJobs(): array
    {
        $this->authorize(QueueManager::class);

        $this->jobProgress->clear();

        return ['released' => true];
    }

    /**
     * @param  list<string>|null  $volumes  Volume handles to index. Omit to index every volume.
     * @param  bool  $createMissingAssets  Whether to create asset records for files that don’t have one.
     * @param  bool  $deleteMissingAssets  Whether to delete asset records whose files are missing.
     * @param  bool  $deleteEmptyFolders  Whether to delete folders that are empty.
     * @return array{volumes: list<string>, output: string}
     */
    #[McpTool(
        name: 'asset-indexes.update',
        description: 'Re-indexes the files in asset volumes, as the Asset Indexes utility does. Large volumes can take a while.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('utility:asset-indexes')]
    public function updateAssetIndexes(
        ?array $volumes = null,
        bool $createMissingAssets = true,
        bool $deleteMissingAssets = false,
        bool $deleteEmptyFolders = false,
    ): array {
        $this->authorize(AssetIndexes::class);

        $handles = $volumes ?? $this->volumes->getAllVolumes()->pluck('handle')->all();

        foreach ($handles as $handle) {
            if ($this->volumes->getVolumeByHandle($handle) === null) {
                throw new ToolCallException("Volume [$handle] not found.");
            }
        }

        $options = [
            '--createMissingAssets' => $createMissingAssets ? 'true' : 'false',
            '--deleteMissingAssets' => $deleteMissingAssets ? 'true' : 'false',
            '--deleteEmptyFolders' => $deleteEmptyFolders ? 'true' : 'false',
            '--no-interaction' => true,
        ];
        $output = '';

        foreach ($handles as $handle) {
            Artisan::call('craft:index-assets:one', ['handle' => $handle, ...$options]);
            $output .= Artisan::output();
        }

        return ['volumes' => array_values($handles), 'output' => trim($output)];
    }

    /** @param  class-string<Utility>  $utility */
    private function authorize(string $utility): void
    {
        $id = $utility::id();

        if (in_array($id, $this->generalConfig->disabledUtilities, true)) {
            throw new ToolCallException("The $id utility is disabled.");
        }

        if (! $this->actor->user()->can("utility:$id")) {
            throw new ToolCallException("Access to the $id utility is required.");
        }
    }
}
