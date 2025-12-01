<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;

/**
 * BaseBatchedElementJob is the base class for large jobs that may need to spawn
 * additional jobs to complete the workload, which perform actions on elements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
abstract class BaseBatchedElementJob extends BaseBatchedJob
{
    /** @internal */
    public string $bulkOpKey;

    /**
     * @inheritdoc
     */
    protected function before(): void
    {
        $this->bulkOpKey = Craft::$app->getElements()->beginBulkOp();
    }

    /**
     * @inheritdoc
     */
    protected function beforeBatch(): void
    {
        Craft::$app->getElements()->resumeBulkOp($this->bulkOpKey);
    }

    /**
     * @inheritdoc
     */
    protected function after(): void
    {
        Craft::$app->getElements()->endBulkOp($this->bulkOpKey);
    }
}
