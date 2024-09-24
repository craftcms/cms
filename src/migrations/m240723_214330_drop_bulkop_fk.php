<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240723_214330_drop_bulkop_fk migration.
 */
class m240723_214330_drop_bulkop_fk extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists(Table::ELEMENTS_BULKOPS, ['elementId']);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240723_214330_drop_bulkop_fk cannot be reverted.\n";
        return false;
    }
}
