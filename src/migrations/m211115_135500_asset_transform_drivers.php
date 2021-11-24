<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;

/**
 * m211115_135500_asset_transform_drivers migration.
 */
class m211115_135500_asset_transform_drivers extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Rename tables
        $this->renameTable('{{%assettransforms}}', Table::IMAGETRANSFORMS);
        $this->renameTable('{{%assettransformindex}}', Table::IMAGETRANSFORMINDEX);

        // Drop index, column and re-add index
        $this->dropIndexIfExists(Table::IMAGETRANSFORMINDEX, ['volumeId', 'assetId', 'location']);
        $this->dropColumn(Table::IMAGETRANSFORMINDEX, 'volumeId');
        $this->createIndex(null, Table::IMAGETRANSFORMINDEX, ['assetId', 'location'], false);

        // Add the driver info.
        $this->addColumn(Table::IMAGETRANSFORMINDEX, 'driver', $this->string()->null()->after('assetId'));
        $this->addColumn(Table::IMAGETRANSFORMS, 'driver',  $this->string()->null()->after('mode'));

        // Rename the location to `transformString`
        $this->renameColumn(Table::IMAGETRANSFORMINDEX, 'location', 'transformString');

        // Rename dimension change time to parameter change time
        $this->renameColumn(Table::IMAGETRANSFORMS, 'dimensionChangeTime', 'parameterChangeTime');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211115_135500_asset_transform_drivers cannot be reverted.\n";
        return false;
    }
}
