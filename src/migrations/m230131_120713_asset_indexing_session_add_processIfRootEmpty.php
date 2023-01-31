<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230131_120713_asset_indexing_session_add_processIfRootEmpty migration.
 */
class m230131_120713_asset_indexing_session_add_processIfRootEmpty extends Migration
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
        return true;
    }
}
