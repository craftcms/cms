<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230131_120713_asset_indexing_session_new_options migration.
 */
class m230131_120713_asset_indexing_session_new_options extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::ASSETINDEXINGSESSIONS);

        if (!isset($table->columns['processIfRootEmpty'])) {
            $this->addColumn(
                Table::ASSETINDEXINGSESSIONS,
                'processIfRootEmpty',
                $this->boolean()->defaultValue(false)->after('actionRequired'),
            );
        }
        if (!isset($table->columns['listEmptyFolders'])) {
            $this->addColumn(
                Table::ASSETINDEXINGSESSIONS,
                'listEmptyFolders',
                $this->boolean()->defaultValue(false)->after('cacheRemoteImages'),
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::ASSETINDEXINGSESSIONS);
        if (isset($table->columns['processIfRootEmpty'])) {
            $this->dropColumn(Table::ASSETINDEXINGSESSIONS, 'processIfRootEmpty');
        }
        if (isset($table->columns['listEmptyFolders'])) {
            $this->dropColumn(Table::ASSETINDEXINGSESSIONS, 'listEmptyFolders');
        }
        return true;
    }
}
