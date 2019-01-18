<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m181011_160000_soft_delete_asset_support migration.
 */
class m181011_160000_soft_delete_asset_support extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Unique file names in folder should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::ASSETS, ['filename', 'folderId'], true, $this);
        $this->createIndex(null, Table::ASSETS, ['filename', 'folderId'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181011_160000_soft_delete_asset_support cannot be reverted.\n";
        return false;
    }
}
