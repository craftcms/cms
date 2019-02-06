<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190205_140000_fix_asset_soft_delete_index migration.
 */
class m190205_140000_fix_asset_soft_delete_index extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Unique file names in folder should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::ASSETS, ['filename', 'folderId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::ASSETS, ['filename', 'folderId'], false, $this);
        $this->createIndex(null, Table::ASSETS, ['filename', 'folderId'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190205_140000_fix_asset_soft_delete_index cannot be reverted.\n";
        return false;
    }
}
