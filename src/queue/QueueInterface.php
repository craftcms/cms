<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use yii\base\InvalidArgumentException;

/**
 * QueueInterface defines the common interface to be implemented by queue classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface QueueInterface
{
    /**
     * Runs all the queued-up jobs.
     */
    public function run();

    /**
     * Re-adds a failed job to the queue.
     *
     * @param string $id
     */
    public function retry(string $id);

    /**
     * Retries all failed jobs.
     *
     * @since 3.4.0
     */
    public function retryAll();

    /**
     * Releases all jobs.
     *
     * @since 3.4.0
     */
    public function releaseAll();

    /**
     * Releases a job from the queue.
     *
     * @param string $id
     */
    public function release(string $id);

    /**
     * Sets the progress for the currently reserved job.
     *
     * @param int $progress The job progress (1-100)
     * @param string|null $label The progress label
     */
    public function setProgress(int $progress, string $label = null);

    /**
     * Returns whether there are any waiting jobs.
     *
     * @return bool
     */
    public function getHasWaitingJobs(): bool;

    /**
     * Returns whether there are any reserved jobs.
     *
     * @return bool
     */
    public function getHasReservedJobs(): bool;

    /**
     * Returns the total number of jobs in the queue.
     *
     * @return int|float
     * @since 3.4.0
     */
    public function getTotalJobs();

    /**
     * Returns info about the jobs in the queue.
     *
     * The response array should have sub-arrays with the following keys:
     *
     * - `id` – the job ID
     * - `status` – the job status (1 = waiting, 2 = reserved, 3 = done, 4 = failed)
     * - `progress` – the job progress (0-100)
     * - `description` – the job description
     * - `error` – the error message (if the job failed)
     *
     * @param int|null $limit
     * @return array
     */
    public function getJobInfo(int $limit = null): array;

    /**
     * Returns detailed info about a single job.
     *
     * The response array can contain the following keys:
     *
     * - `status` – the job status (1 = waiting, 2 = reserved, 3 = done, 4 = failed)
     * - `progress` – the job progress (0-100)
     * - `description` – the job description
     * - `ttr` – the job’s time-to-reserve, in seconds
     * - `error` – the error message (if the job failed)
     * - `job` – the deserialized job
     *
     * Any other key/value pairs are allowed and will be displayed on the details page.
     *
     * @param string $id
     * @return array
     * @throws InvalidArgumentException if $id is an invalid job ID
     * @since 3.4.0
     */
    public function getJobDetails(string $id): array;
}
