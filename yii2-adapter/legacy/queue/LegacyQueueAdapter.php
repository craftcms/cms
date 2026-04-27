<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

/**
 * Provides a queue interface that legacy jobs expect when calling $queue->setProgress().
 *
 * This adapter bridges the gap between legacy Yii2-style jobs and Laravel's queue system,
 * forwarding progress updates to the LegacyJobWrapper which then updates the JobProgressService.
 *
 * @internal
 *
 * @deprecated 6.0.0
 */
class LegacyQueueAdapter
{
    public function __construct(
        private readonly LegacyJobWrapper $wrapper,
    ) {
    }

    /**
     * Sets the progress for the job.
     *
     * @param  int  $progress  Progress percentage (0-100)
     * @param  string|null  $label  Optional progress label
     */
    public function setProgress(int $progress, ?string $label = null): void
    {
        $this->wrapper->updateProgress($progress, $label);
    }
}
