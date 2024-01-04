<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\NotSupportedException;

/**
 * Non-transactional database connection.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
class NonTransactionalConnection extends Connection
{
    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function beginTransaction($isolationLevel = null)
    {
        if ($this->getTransaction() !== null) {
            throw new NotSupportedException(sprintf('Nested transactions aren’t supported by %s', __CLASS__));
        }

        return parent::beginTransaction($isolationLevel);
    }
}
