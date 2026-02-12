<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250206_135036_search_index_queue migration.
 */
class m250206_135036_search_index_queue extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->safeDown();
        $this->createTable(Table::SEARCHINDEXQUEUE, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'reserved' => $this->boolean()->notNull()->defaultValue(false),
        ]);
        $this->createTable(Table::SEARCHINDEXQUEUE_FIELDS, [
            'jobId' => $this->integer()->notNull(),
            'fieldHandle' => $this->string()->notNull(),
            'PRIMARY KEY([[jobId]], [[fieldHandle]])',
        ]);
        $this->createIndex(null, Table::SEARCHINDEXQUEUE, ['elementId', 'siteId', 'reserved'], false);
        $this->createIndex(null, Table::SEARCHINDEXQUEUE_FIELDS, ['jobId', 'fieldHandle'], true);
        $this->addForeignKey(null, Table::SEARCHINDEXQUEUE, 'elementId', Table::ELEMENTS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::SEARCHINDEXQUEUE_FIELDS, 'jobId', Table::SEARCHINDEXQUEUE, 'id', 'CASCADE', null);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::SEARCHINDEXQUEUE_FIELDS);
        $this->dropTableIfExists(Table::SEARCHINDEXQUEUE);
        return true;
    }
}
