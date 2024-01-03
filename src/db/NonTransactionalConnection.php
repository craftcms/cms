<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\NotSupportedException;
use yii\db\Command as YiiCommand;
use yii\db\Connection as YiiConnection;
use yii\di\Instance;

/**
 * Non-transactional database connection.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
class NonTransactionalConnection extends Connection
{
    public YiiConnection|string $primaryDb = 'db';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->primaryDb = Instance::ensure($this->primaryDb, YiiConnection::class);
    }

    /**
     * @inheritdoc
     */
    public function createCommand($sql = null, $params = []): YiiCommand
    {
        // stick with the primary connection as long as we can
        if (!$this->getIsActive() && $this->primaryDb->getTransaction() === null) {
            return $this->primaryDb->useMaster(
                fn() => $this->primaryDb->createCommand($sql, $params),
            );
        }

        return parent::createCommand($sql, $params);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function beginTransaction($isolationLevel = null)
    {
        throw new NotSupportedException(sprintf('Transactions aren’t supported by %s', __CLASS__));
    }
}
