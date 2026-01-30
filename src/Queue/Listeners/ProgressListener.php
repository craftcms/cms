<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Queue\Contracts\DescribableJob;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Support\Facades\Queue;

abstract readonly class ProgressListener
{
    public function __construct(
        protected GeneralConfig $generalConfig,
        protected JobProgress $progress,
    ) {}

    protected function shouldTrackQueue(?string $queueName): bool
    {
        $defaultQueueName = Queue::getConfig()['queue'] ?? $this->generalConfig->queueName;

        return in_array($queueName ?? $defaultQueueName, $this->generalConfig->trackedQueueNames, true);
    }

    protected function jobUuid(array $payload): ?string
    {
        return $payload['uuid'] ?? $payload['id'] ?? null;
    }

    protected function jobDescription(mixed $job): string
    {
        if ($job instanceof DescribableJob) {
            return $job->getDescription();
        }

        if (is_object($job)) {
            return $job::class;
        }

        return 'Unknown job';
    }
}
