<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m231102_113100_add_entry_type_show_slug_field migration.
 */
class m231102_113100_add_entry_type_show_slug_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRYTYPES, 'showSlugField', $this->boolean()->defaultValue(true)->after('titleFormat'));
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
