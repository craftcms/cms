<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use CraftCms\Cms\Support\Facades\BulkOps;

/**
 * BaseBatchedElementJob is the base class for large jobs that may need to spawn
 * additional jobs to complete the workload, which perform actions on elements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.0.0
 * @deprecated in Craft 6.0.0. Use [[CraftCms\Cms\Queue\BatchedElementJob]] instead.
 */
abstract class BaseBatchedElementJob extends BaseBatchedJob
{
    /** @internal */
    public string $bulkOpKey;

    /**
     * {@inheritdoc}
     */
    protected function before(): void
    {
        $this->bulkOpKey = BulkOps::start();
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeBatch(): void
    {
        BulkOps::resume($this->bulkOpKey);
    }

    /**
     * {@inheritdoc}
     */
    protected function after(): void
    {
        BulkOps::end($this->bulkOpKey);
    }
}
