<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

/**
 * ReleasableQueueInterface defines the common interface to be implemented by queue classes that can release jobs.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.7.0
 */
interface ReleasableQueueInterface
{
    /**
     * Releases all jobs.
     */
    public function releaseAll(): void;

    /**
     * Releases a job from the queue.
     *
     * @param string $id
     */
    public function release(string $id): void;
}
