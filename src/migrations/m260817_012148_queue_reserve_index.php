<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m260817_012148_queue_reserve_index migration.
 */
class m260817_012148_queue_reserve_index extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createIndexIfMissing(Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'priority', 'id', 'timePushed', 'delay'], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropIndexIfExists(Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'priority', 'id', 'timePushed', 'delay'], false);

        return true;
    }
}
