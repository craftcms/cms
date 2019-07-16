<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m180731_162030_soft_delete_sites migration.
 */
class m180731_162030_soft_delete_sites extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn(Table::SITES, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::SITES, ['dateDeleted'], false);

        // Unique site handles should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::SITES, ['handle'], true, $this);
        $this->createIndex(null, Table::SITES, ['handle'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180731_162030_soft_delete_sites cannot be reverted.\n";
        return false;
    }
}
