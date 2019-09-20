<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160714_000000_increase_max_asset_filesize extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(Table::ASSETINDEXDATA, 'size', 'bigint(20) unsigned DEFAULT NULL');
        $this->alterColumn(Table::ASSETS, 'size', 'bigint(20) unsigned DEFAULT NULL');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160714_000000_increase_max_asset_filesize cannot be reverted.\n";

        return false;
    }
}
