<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m210331_220322_null_author migration.
 */
class m210331_220322_null_author extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropForeignKeyIfExists(Table::ENTRIES, ['authorId'], $this);
        $this->addForeignKey(null, Table::ENTRIES, ['authorId'], Table::USERS, ['id'], 'SET NULL');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210331_220322_null_author cannot be reverted.\n";
        return false;
    }
}
