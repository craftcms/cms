<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m170206_142126_system_name migration.
 */
class m170217_120224_asset_indexing_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists(Table::ASSETINDEXDATA, 'offset')) {
            // Migration has already run
            return true;
        }

        $this->addColumn(Table::ASSETINDEXDATA, 'inProgress', $this->boolean()->after('recordId')->defaultValue(false));
        $this->addColumn(Table::ASSETINDEXDATA, 'completed', $this->boolean()->after('inProgress')->defaultValue(false));

        MigrationHelper::dropIndexIfExists(Table::ASSETINDEXDATA, ['sessionId', 'volumeId', 'offset'], true, $this);

        $this->dropColumn(Table::ASSETINDEXDATA, 'offset');

        $this->createIndex(null, Table::ASSETINDEXDATA, ['sessionId', 'volumeId']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170217_120224_asset_indexing_columns cannot be reverted.\n";

        return false;
    }
}
