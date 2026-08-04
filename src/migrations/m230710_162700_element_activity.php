<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230710_162700_element_activity migration.
 */
class m230710_162700_element_activity extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::ELEMENTACTIVITY);

        $this->createTable(Table::ELEMENTACTIVITY, [
            'elementId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'draftId' => $this->integer()->null(),
            'type' => $this->string()->notNull(),
            'timestamp' => $this->dateTime(),
            'PRIMARY KEY([[elementId]], [[userId]], [[type]])',
        ]);

        $this->createIndex(null, Table::ELEMENTACTIVITY, ['elementId', 'timestamp', 'userId'], false);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['siteId'], Table::SITES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['draftId'], Table::DRAFTS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230710_162700_element_activity cannot be reverted.\n";
        return false;
    }
}
