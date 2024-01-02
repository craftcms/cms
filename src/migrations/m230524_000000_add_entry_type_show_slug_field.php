<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230524_000000_add_entry_type_show_slug_field migration.
 */
class m230524_000000_add_entry_type_show_slug_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::ENTRYTYPES, 'showSlugField')) {
            $this->addColumn(Table::ENTRYTYPES, 'showSlugField', $this->boolean()->defaultValue(true)->after('titleFormat'));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::ENTRYTYPES, 'showSlugField');
        return true;
    }
}
