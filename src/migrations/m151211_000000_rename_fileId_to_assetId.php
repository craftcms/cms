<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151211_000000_rename_fileId_to_assetId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameColumn(Table::ASSETTRANSFORMINDEX, 'fileId', 'assetId');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151211_000000_rename_fileId_to_assetId cannot be reverted.\n";

        return false;
    }
}
