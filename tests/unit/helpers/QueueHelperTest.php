<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\base\Batchable;
use craft\helpers\Queue as QueueHelper;
use craft\queue\BaseBatchedJob;
use craft\test\TestCase;
use yii\queue\ExecEvent;
use yii\queue\PushEvent;
use yii\queue\Queue as YiiQueue;
use yii\queue\RetryableJobInterface;
use yii\queue\sync\Queue as SyncQueue;

/**
 * Verifies that batched jobs use the queue's effective TTR.
 */
class QueueHelperTest extends TestCase
{
    /**
     * The default TTR must remain available to the job and its next batch.
     */
    public function testDefaultTtrIsPreservedForFollowingBatches(): void
    {
        $queue = Craft::$app->getQueue();
        $job = new QueueHelperBatchedJob();
        $this->assertPushTtr($job, null, null, $queue->ttr);

        // BaseBatchedJob clones itself and passes its TTR when pushing the next batch.
        $nextJob = clone $job;
        $nextJob->batchIndex++;
        $nextJob->itemOffset = 100;
        $this->assertPushTtr($nextJob, $queue, $job->ttr, $queue->ttr);

        self::assertSame(1, $nextJob->batchIndex);
        self::assertSame(100, $nextJob->itemOffset);
    }

    /**
     * @dataProvider customQueueTtrDataProvider
     * @param bool $retryable Whether the job defines its own retryable TTR
     * @param int|null $ttr The TTR passed to the helper
     * @param int $expected The effective TTR used by the target queue
     */
    public function testCustomQueueTtr(bool $retryable, ?int $ttr, int $expected): void
    {
        $queue = new SyncQueue(['ttr' => 120]);
        $job = $retryable ? new QueueHelperRetryableJob() : new QueueHelperBatchedJob();
        $this->assertPushTtr($job, $queue, $ttr, $expected);
    }

    /**
     * @return array<string, array{bool, int|null, int}>
     */
    public static function customQueueTtrDataProvider(): array
    {
        return [
            'target queue default' => [false, null, 120],
            'explicit override' => [false, 45, 45],
            'retryable default' => [true, null, 90],
            'explicit retryable override' => [true, 45, 45],
            'zero follows Yii fallback' => [false, 0, 120],
        ];
    }

    /**
     * Old serialized jobs must use their metadata TTR, rather than the queue default.
     */
    public function testExecutingOldBatchRestoresItsMetadataTtr(): void
    {
        $queue = Craft::$app->getQueue();
        $defaultTtr = $queue->ttr;
        $queue->ttr = 3600;
        $job = new QueueHelperBatchedJob();
        $job->ttr = null;
        $job = unserialize(serialize($job));

        try {
            $queue->trigger(YiiQueue::EVENT_BEFORE_EXEC, new ExecEvent([
                'id' => 'old-batch',
                'job' => $job,
                'ttr' => 120,
            ]));

            self::assertSame(120, $job->ttr);
        } finally {
            $queue->trigger(YiiQueue::EVENT_AFTER_EXEC, new ExecEvent(['job' => $job]));
            $queue->ttr = $defaultTtr;
        }
    }

    /**
     * Executing a job must preserve an already configured internal time limit.
     */
    public function testExecutingBatchPreservesItsConfiguredTtr(): void
    {
        $queue = Craft::$app->getQueue();
        $job = new QueueHelperBatchedJob(['ttr' => 60]);

        try {
            $queue->trigger(YiiQueue::EVENT_BEFORE_EXEC, new ExecEvent([
                'id' => 'configured-batch',
                'job' => $job,
                'ttr' => 120,
            ]));

            self::assertSame(60, $job->ttr);
        } finally {
            $queue->trigger(YiiQueue::EVENT_AFTER_EXEC, new ExecEvent(['job' => $job]));
        }
    }

    /**
     * Captures the real push event before serialization or persistence occurs.
     *
     * @param BaseBatchedJob $job The batch to push
     * @param YiiQueue|null $queue The target queue, or null for Craft's default queue
     * @param int|null $ttr The TTR passed to the helper
     * @param int $expected The expected effective TTR
     */
    private function assertPushTtr(BaseBatchedJob $job, ?YiiQueue $queue, ?int $ttr, int $expected): void
    {
        $targetQueue = $queue ?? Craft::$app->getQueue();
        $captured = false;
        $handler = function(PushEvent $event) use ($job, $expected, &$captured) {
            $event->handled = true;
            $captured = true;
            self::assertSame($job, $event->job);
            self::assertSame($expected, $event->ttr);
            self::assertSame($expected, $job->ttr);
        };
        $targetQueue->on(YiiQueue::EVENT_BEFORE_PUSH, $handler);

        try {
            QueueHelper::push($job, ttr: $ttr, queue: $queue);
            self::assertTrue($captured);
        } finally {
            $targetQueue->off(YiiQueue::EVENT_BEFORE_PUSH, $handler);
        }
    }
}

/**
 * A concrete batch whose description does not load elements.
 */
class QueueHelperBatchedJob extends BaseBatchedJob
{
    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        return new class() implements Batchable {
            /**
             * @inheritdoc
             */
            public function count(): int
            {
                return 0;
            }

            /**
             * @inheritdoc
             */
            public function getSlice(int $offset, int $limit): iterable
            {
                return [];
            }
        };
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
    }
}

/**
 * A batch whose timeout comes from the retryable job contract.
 */
class QueueHelperRetryableJob extends QueueHelperBatchedJob implements RetryableJobInterface
{
    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        return 90;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        return false;
    }
}
