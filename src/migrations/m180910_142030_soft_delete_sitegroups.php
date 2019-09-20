<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m180910_142030_soft_delete_sitegroups migration.
 */
class m180910_142030_soft_delete_sitegroups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn(Table::SITEGROUPS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::SITEGROUPS, ['dateDeleted'], false);

        // Unique site group names should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::SITEGROUPS, ['name'], true, $this);
        $this->createIndex(null, Table::SITEGROUPS, ['name'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180910_142030_soft_delete_sitegroups cannot be reverted.\n";
        return false;
    }
}
