<?php

namespace CraftCms\Yii2Adapter;

use CraftCms\Cms\Support\Facades\DB;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\Transaction;

final class LaravelTransaction extends Transaction
{
    private $_level = 0;

    public function getIsActive()
    {
        return $this->_level > 0 && $this->db && $this->db->isActive;
    }

    public function begin($isolationLevel = null)
    {
        if ($this->db === null) {
            throw new InvalidConfigException('Transaction::db must be set.');
        }
        $this->db->open();

        if (DB::transactionLevel() === 0 && !is_null($isolationLevel)) {
            DB::statement("SET TRANSACTION ISOLATION LEVEL $isolationLevel");
        }

        Yii::debug('Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : ''), __METHOD__);
        $this->db->trigger(Connection::EVENT_BEGIN_TRANSACTION);
        DB::beginTransaction();

        $this->_level++;
    }

    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->_level--;

        $this->db->trigger(Connection::EVENT_COMMIT_TRANSACTION);

        DB::commit();
    }

    public function rollBack()
    {
        if (!$this->getIsActive()) {
            // do nothing if transaction is not active: this could be the transaction is committed
            // but the event handler to "commitTransaction" throw an exception
            return;
        }

        $this->_level--;

        $this->db->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);

        DB::rollBack();
    }

    public function getLevel()
    {
        return $this->_level;
    }
}
