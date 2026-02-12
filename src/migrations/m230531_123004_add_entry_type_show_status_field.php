<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230531_123004_add_entry_type_show_status_field migration.
 */
class m230531_123004_add_entry_type_show_status_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRYTYPES, 'showStatusField', $this->boolean()->defaultValue(true)->after('titleFormat'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::ENTRYTYPES, 'showStatusField');
        return true;
    }
}
