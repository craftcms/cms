<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250119_135304_entry_type_overrides migration.
 */
class m250119_135304_entry_type_overrides extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::SECTIONS_ENTRYTYPES, 'name')) {
            $this->addColumn(Table::SECTIONS_ENTRYTYPES, 'name', $this->string()->after('sortOrder'));
            $this->addColumn(Table::SECTIONS_ENTRYTYPES, 'handle', $this->string()->after('name'));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::SECTIONS_ENTRYTYPES, 'name');
        $this->dropColumn(Table::SECTIONS_ENTRYTYPES, 'handle');
        return true;
    }
}
