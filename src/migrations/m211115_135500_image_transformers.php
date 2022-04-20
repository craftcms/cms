<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m211115_135500_image_transformers migration.
 */
class m211115_135500_image_transformers extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::IMAGETRANSFORMINDEX);
        $this->dropTableIfExists(Table::IMAGETRANSFORMS);

        // Rename tables
        $this->renameTable('{{%assettransforms}}', Table::IMAGETRANSFORMS);
        $this->renameTable('{{%assettransformindex}}', Table::IMAGETRANSFORMINDEX);

        // Drop index, column and re-add index
        $this->dropIndexIfExists(Table::IMAGETRANSFORMINDEX, ['volumeId', 'assetId', 'location']);
        $this->dropColumn(Table::IMAGETRANSFORMINDEX, 'volumeId');
        $this->createIndex(null, Table::IMAGETRANSFORMINDEX, ['assetId', 'location'], false);

        // Add the transformer info.
        $this->addColumn(Table::IMAGETRANSFORMINDEX, 'transformer', $this->string()->null()->after('assetId'));

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
        echo "m211115_135500_image_transformers cannot be reverted.\n";
        return false;
    }
}
