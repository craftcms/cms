<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m231213_030600_element_bulk_ops migration.
 */
class m231213_030600_element_bulk_ops extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::ELEMENTS_BULKOPS);
        $this->createTable(Table::ELEMENTS_BULKOPS, [
            'elementId' => $this->integer(),
            'key' => $this->char(10)->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[elementId]], [[key]])',
        ]);
        $this->addForeignKey(null, Table::ELEMENTS_BULKOPS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->createIndex(null, Table::ELEMENTS_BULKOPS, ['timestamp'], false);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231213_030600_element_bulk_ops cannot be reverted.\n";
        return false;
    }
}
