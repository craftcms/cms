<?php

namespace craft\migrations;

use craft\db\Migration;

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
        $this->renameColumn('{{%assettransformindex}}', 'fileId', 'assetId');
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
