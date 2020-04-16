<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m191216_191635_asset_upload_tracking migration.
 */
class m191216_191635_asset_upload_tracking extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ASSETS, 'uploaderId', $this->integer()->after('folderId'));
        $this->addForeignKey(null, Table::ASSETS, ['uploaderId'], Table::USERS, ['id'], 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropForeignKeyIfExists(Table::ASSETS, ['uploaderId'], $this);
        $this->dropColumn(Table::ASSETS, 'uploaderId');
    }
}
