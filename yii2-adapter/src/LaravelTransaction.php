<?php

namespace CraftCms\Yii2Adapter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use yii\db\Connection;
use yii\db\Transaction;

final class LaravelTransaction extends Transaction
{
    public function getIsActive()
    {
        return DB::transactionLevel() > 0;
    }

    public function begin($isolationLevel = null)
    {
        Log::debug('Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : ''), [__METHOD__]);
        $this->db->trigger(Connection::EVENT_BEGIN_TRANSACTION);
        DB::beginTransaction();
    }

    public function commit()
    {
        if (DB::transactionLevel() === 0) {
            return;
        }

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

        $this->db->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);

        DB::rollBack();
    }

    public function getLevel()
    {
        return DB::transactionLevel();
    }
}
