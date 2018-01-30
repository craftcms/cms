<?php

namespace craft\migrations;

use craft\db\Migration;
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
        if (!$this->db->columnExists('{{%assetindexdata}}', 'offset')) {
            // Migration has already run
            return true;
        }

        $this->addColumn('{{%assetindexdata}}', 'inProgress', $this->boolean()->after('recordId')->defaultValue(false));
        $this->addColumn('{{%assetindexdata}}', 'completed', $this->boolean()->after('inProgress')->defaultValue(false));

        MigrationHelper::dropIndexIfExists('{{%assetindexdata}}', ['sessionId', 'volumeId', 'offset'], true, $this);

        $this->dropColumn('{{%assetindexdata}}', 'offset');

        $this->createIndex(null, '{{%assetindexdata}}', ['sessionId', 'volumeId']);

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
