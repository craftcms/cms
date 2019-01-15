<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180810_214427_soft_delete_field_layouts migration.
 */
class m180810_214427_soft_delete_field_layouts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn(Table::FIELDLAYOUTS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::FIELDLAYOUTS, ['dateDeleted'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180810_214427_soft_delete_field_layouts cannot be reverted.\n";
        return false;
    }
}
