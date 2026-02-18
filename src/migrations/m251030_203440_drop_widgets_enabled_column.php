<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m251030_203440_drop_widgets_enabled_column migration.
 */
class m251030_203440_drop_widgets_enabled_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->columnExists(Table::WIDGETS, 'enabled')) {
            $this->dropColumn(Table::WIDGETS, 'enabled');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->addColumn(Table::WIDGETS, 'enabled', $this->boolean()->defaultValue(true)->notNull()->after('settings'));
        return true;
    }
}
