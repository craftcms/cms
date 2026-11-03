<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m261014_000000_field_tips_and_warnings migration.
 */
class m261014_000000_field_tips_and_warnings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FIELDS, 'tip')) {
            $this->addColumn(Table::FIELDS, 'tip', $this->text()->after('instructions'));
        }
        if (!$this->db->columnExists(Table::FIELDS, 'warning')) {
            $this->addColumn(Table::FIELDS, 'warning', $this->text()->after('tip'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::FIELDS, 'tip');
        $this->dropColumn(Table::FIELDS, 'warning');

        return true;
    }
}
