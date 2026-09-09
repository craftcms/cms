<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Models\JobProgress as JobProgressModel;

/**
 * Queue properties are read during JSON encoding, after page props can enqueue jobs.
 * Inertia would resolve Arrayable or JsonSerializable implementations too early.
 */
class QueueState
{
    public ?JobProgressModel $displayedJob {
        get => $this->state()['displayedJob'];
    }

    public bool $hasReservedJobs {
        get => $this->state()['hasReservedJobs'];
    }

    public bool $hasWaitingJobs {
        get => $this->state()['hasWaitingJobs'];
    }

    /** @var array{displayedJob: ?JobProgressModel, hasReservedJobs: bool, hasWaitingJobs: bool}|null */
    private ?array $state = null;

    public function __construct(private readonly JobProgress $progress) {}

    /** @return array{displayedJob: ?JobProgressModel, hasReservedJobs: bool, hasWaitingJobs: bool} */
    private function state(): array
    {
        return $this->state ??= [
            'displayedJob' => $this->progress->getDisplayedJob(),
            'hasReservedJobs' => $this->progress->hasReservedJobs(),
            'hasWaitingJobs' => $this->progress->hasPendingJobs(),
        ];
    }
}
