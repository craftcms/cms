<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m251205_190131_drop_searchindexqueue_fk migration.
 */
class m251205_190131_drop_searchindexqueue_fk extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists(Table::SEARCHINDEXQUEUE, 'elementId');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->addForeignKey(null, Table::SEARCHINDEXQUEUE, 'elementId', Table::ELEMENTS, 'id', 'CASCADE', null);
        return true;
    }
}
