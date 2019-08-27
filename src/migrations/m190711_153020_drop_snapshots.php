<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m190711_153020_drop_snapshots migration.
 */
class m190711_153020_drop_snapshots extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists(Table::REVISIONS, 'snapshot')) {
            $this->dropColumn(Table::REVISIONS, 'snapshot');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190711_153020_drop_snapshots cannot be reverted.\n";
        return false;
    }
}
