<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230105_095357_add_fsSubpath_to_volumes migration.
 */
class m230105_095357_add_fsSubpath_to_volumes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::VOLUMES, 'fsSubpath', $this->string()->null()->after('fs'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::VOLUMES);
        if (isset($table->columns['fsSubpath'])) {
            $this->dropColumn(Table::VOLUMES, 'fsSubpath');
        }
        return true;
    }
}
