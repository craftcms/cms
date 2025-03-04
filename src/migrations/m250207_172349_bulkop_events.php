<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250207_172349_bulkop_events migration.
 */
class m250207_172349_bulkop_events extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->safeDown();
        $this->createTable(Table::BULKOPEVENTS, [
            'key' => $this->char(10)->notNull(),
            'senderClass' => $this->string()->notNull(),
            'eventName' => $this->string()->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[key]], [[senderClass]], [[eventName]])',
        ]);
        $this->createIndex(null, Table::BULKOPEVENTS, ['timestamp'], false);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::BULKOPEVENTS);
        return true;
    }
}
