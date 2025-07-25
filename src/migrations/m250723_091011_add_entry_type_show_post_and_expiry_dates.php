<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250723_091011_add_entry_type_show_post_and_expiry_dates migration.
 */
class m250723_091011_add_entry_type_show_post_and_expiry_dates extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRYTYPES, 'showExpiryDateField', $this->boolean()->defaultValue(true)->after('showStatusField'));
        $this->addColumn(Table::ENTRYTYPES, 'showPostDateField', $this->boolean()->defaultValue(true)->after('showStatusField'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::ENTRYTYPES, 'showExpiryDateField')) {
            $this->dropColumn(Table::ENTRYTYPES, 'showExpiryDateField');
        }
        if ($this->db->columnExists(Table::ENTRYTYPES, 'showPostDateField')) {
            $this->dropColumn(Table::ENTRYTYPES, 'showPostDateField');
        }

        return true;
    }
}
